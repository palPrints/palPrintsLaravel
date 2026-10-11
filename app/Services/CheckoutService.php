<?php

namespace App\Services;

use App\Models\Address;
use App\Models\BranchProductOffering;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrintFile;
use App\Models\User;
use App\Support\AdminNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function completeDemoPayment(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart' => 'السلة فارغة، أضيفي ملفات طباعة الورق قبل إتمام الطلب.',
                ]);
            }

            $cart->load([
                'items.product',
                'items.variant',
                'items.design',
                'items.printFiles' => fn ($query) => $query->where('status', '!=', PrintFile::STATUS_DELETED),
            ]);

            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'السلة فارغة، أضيفي ملفات طباعة الورق قبل إتمام الطلب.',
                ]);
            }

            $address = $this->resolveAddress($user, $data);
            $subtotal = $cart->items->sum(fn (CartItem $item) => (float) $item->unit_price * (int) $item->quantity);
            $shippingCost = $subtotal > 0 ? (float) \App\Support\PlatformSettings::get('fees', 'shipping_cost', 5) : 0.00;
            $discountAmount = 0.00;
            $totalAmount = max(0, $subtotal + $shippingCost - $discountAmount);

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->nextOrderNumber(),
                'shipping_address_id' => $address->id,
                'shipping_address_snapshot' => $this->addressSnapshot($address, $data),
                'status' => 'awaiting_payment_review',
                'payment_status' => 'pending_review',
                'payment_method' => $data['payment_method'] ?? 'palpay',
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cart->items as $cartItem) {
                $this->createOrderItem($order, $cartItem);
            }

            Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'payment_number' => $this->nextPaymentNumber(),
                'status' => 'pending_review',
                'method' => $data['payment_method'] ?? 'palpay',
                'gateway' => $data['payment_method'] ?? 'palpay',
                'gateway_transaction_id' => null,
                'amount' => $totalAmount,
                'currency' => 'ILS',
                'paid_at' => null,
                'gateway_payload' => [
                    'mode' => 'manual_receipt_review',
                    'cart_id' => $cart->id,
                    'receipt_path' => $data['payment_receipt_path'] ?? null,
                    'receipt_disk' => $data['payment_receipt_disk'] ?? null,
                    'receipt_original_name' => $data['payment_receipt_original_name'] ?? null,
                ],
            ]);

            AdminNotifier::toAdmins(
                'order.payment_review',
                'إشعار دفع جديد بانتظار المراجعة',
                sprintf('طلب جديد رقم %s من %s بقيمة %s ₪. راجع إشعار الدفع ووجّه الطلب لمطبعة.', $order->order_number, $user->name, number_format($totalAmount, 2)),
                route('admin.payment-notices'),
            );

            $cart->update([
                'status' => 'converted',
                'converted_at' => now(),
            ]);

            return $order->load('items.printFiles');
        });
    }

    private function resolveAddress(User $user, array $data): Address
    {
        if (! empty($data['address_id'])) {
            return Address::query()
                ->where('user_id', $user->id)
                ->where('is_deleted', false)
                ->findOrFail($data['address_id']);
        }

        return Address::create([
            'user_id' => $user->id,
            'city' => $data['city'],
            'region' => $data['region'] ?? null,
            'street' => $data['street'],
            'building' => $data['building'] ?? null,
            'apartment' => $data['apartment'] ?? null,
            'phone' => $data['phone'],
            'is_default' => ! $user->addresses()->where('is_deleted', false)->exists(),
            'is_deleted' => false,
        ]);
    }

    private function createOrderItem(Order $order, CartItem $cartItem): OrderItem
    {
        if ($cartItem->item_type === CartItem::TYPE_CUSTOMER_UPLOAD) {
            return $this->createCustomerUploadOrderItem($order, $cartItem);
        }

        return $this->createCatalogDesignOrderItem($order, $cartItem);
    }

    private function createCustomerUploadOrderItem(Order $order, CartItem $cartItem): OrderItem
    {
        $options = $cartItem->selected_options ?? [];

        // This type holds paper printing and the customer's own studio designs (a product with their artwork on it).
        // Only paper printing must come with uploaded files: a studio design may be made of text and clip art alone.
        $isPaper = $cartItem->product?->code === 'PAPER-PRINT';

        if ($cartItem->design_id !== null || ! $cartItem->product) {
            throw ValidationException::withMessages([
                'cart' => 'عنصر طباعة الورق في السلة غير صحيح.',
            ]);
        }

        if ($isPaper && $cartItem->printFiles->isEmpty()) {
            throw ValidationException::withMessages([
                'files' => 'لا توجد ملفات مرتبطة بعنصر طباعة الورق.',
            ]);
        }

        $offering = $this->activeOffering(
            (int) ($options['branch_product_offering_id'] ?? 0),
            (int) ($options['print_provider_branch_id'] ?? 0),
            $cartItem
        );

        $filesSnapshot = $cartItem->printFiles->map(fn (PrintFile $file) => [
            'name' => $file->original_name,
            'path' => $file->stored_path,
            'disk' => $file->disk,
            'size' => $file->file_size,
            'page_count' => $file->page_count,
            'mime_type' => $file->mime_type,
        ])->values()->all();

        $quantity = (int) $cartItem->quantity;
        $unitPrice = (float) $cartItem->unit_price;
        $totalPrice = $unitPrice * $quantity;
        $providerCost = (float) $offering->base_price * $quantity;

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $cartItem->product_id,
            'variant_id' => $cartItem->variant_id,
            'design_id' => null,
            'designer_id' => null,
            'item_type' => OrderItem::TYPE_CUSTOMER_UPLOAD,
            'print_provider_branch_id' => $offering->print_provider_branch_id,
            'branch_product_offering_id' => $offering->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'provider_cost' => $providerCost,
            'designer_profit' => 0,
            'platform_commission' => max(0, $totalPrice - $providerCost),
            'selected_options' => array_merge($options, ['files' => $filesSnapshot]),
        ]);

        PrintFile::query()
            ->where('cart_item_id', $cartItem->id)
            ->where('status', '!=', PrintFile::STATUS_DELETED)
            ->update([
                'cart_item_id' => null,
                'order_item_id' => $orderItem->id,
                'status' => PrintFile::STATUS_ATTACHED_TO_ORDER,
                'attached_to_order_at' => now(),
            ]);

        return $orderItem;
    }

    private function createCatalogDesignOrderItem(Order $order, CartItem $cartItem): OrderItem
    {
        if (! $cartItem->design || $cartItem->design->status !== 'published') {
            throw ValidationException::withMessages([
                'cart' => 'يوجد تصميم غير منشور في السلة.',
            ]);
        }

        $offering = BranchProductOffering::query()
            ->where('product_id', $cartItem->product_id)
            ->where('is_active', true)
            ->whereHas('printProviderBranch', fn ($query) => $query->where('is_active', true))
            ->whereHas('branchOfferingVariants', fn ($query) => $query
                ->where('variant_id', $cartItem->variant_id)
                ->where('is_available', true))
            ->orderBy('base_price')
            ->first();

        if (! $offering) {
            throw ValidationException::withMessages([
                'cart' => 'لا يوجد فرع مطبعة متاح لأحد عناصر السلة.',
            ]);
        }

        $quantity = (int) $cartItem->quantity;
        $unitPrice = (float) $cartItem->unit_price;
        $totalPrice = $unitPrice * $quantity;
        $designerProfit = (float) ($cartItem->design->designer_profit ?? 0) * $quantity;
        $providerCost = (float) $offering->base_price * $quantity;

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $cartItem->product_id,
            'variant_id' => $cartItem->variant_id,
            'design_id' => $cartItem->design_id,
            'designer_id' => $cartItem->design->designer_id,
            'item_type' => OrderItem::TYPE_CATALOG_DESIGN,
            'print_provider_branch_id' => $offering->print_provider_branch_id,
            'branch_product_offering_id' => $offering->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'provider_cost' => $providerCost,
            'designer_profit' => $designerProfit,
            'platform_commission' => max(0, $totalPrice - $providerCost - $designerProfit),
            'selected_options' => $cartItem->selected_options,
        ]);

        $this->attachDesignerFiles($order, $orderItem, $cartItem);

        return $orderItem;
    }

    /**
     * The print shop prints from files, so the artwork the designer uploaded into the design goes to it with the order line:
     * each file is copied (the design itself can change or go offline later) and attached like a customer's own upload.
     */
    private function attachDesignerFiles(Order $order, OrderItem $orderItem, CartItem $cartItem): void
    {
        $disk = Storage::disk('local');

        foreach ($cartItem->design->design_payload['files'] ?? [] as $file) {
            if (empty($file['path']) || ! $disk->exists($file['path'])) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $file['path'], PATHINFO_EXTENSION));
            $path = 'customer-print-files/orders/'.$order->id.'/'.Str::uuid().($extension ? '.'.$extension : '');
            $disk->copy($file['path'], $path);

            PrintFile::create([
                'user_id' => $order->user_id,
                'product_id' => $cartItem->product_id,
                'order_item_id' => $orderItem->id,
                'original_name' => $file['name'] ?? basename($path),
                'stored_path' => $path,
                'disk' => 'local',
                'mime_type' => $file['mime_type'] ?? null,
                'extension' => $extension,
                'file_size' => $file['size'] ?? $disk->size($path),
                'page_count' => 1,
                'status' => PrintFile::STATUS_ATTACHED_TO_ORDER,
                'uploaded_at' => now(),
                'attached_to_order_at' => now(),
            ]);
        }
    }

    private function activeOffering(int $offeringId, int $branchId, CartItem $cartItem): BranchProductOffering
    {
        $offering = BranchProductOffering::query()
            ->whereKey($offeringId)
            ->where('print_provider_branch_id', $branchId)
            ->where('product_id', $cartItem->product_id)
            ->where('is_active', true)
            ->whereHas('printProviderBranch', fn ($query) => $query->where('is_active', true))
            ->whereHas('branchOfferingVariants', fn ($query) => $query
                ->where('variant_id', $cartItem->variant_id)
                ->where('is_available', true))
            ->first();

        if (! $offering) {
            throw ValidationException::withMessages([
                'cart' => 'فرع المطبعة المختار لطباعة الورق لم يعد متاحاً.',
            ]);
        }

        return $offering;
    }

    private function addressSnapshot(Address $address, array $data): array
    {
        return [
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'city' => $address->city,
            'region' => $address->region,
            'street' => $address->street,
            'building' => $address->building,
            'apartment' => $address->apartment,
        ];
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'PLP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    private function nextPaymentNumber(): string
    {
        do {
            $number = 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Payment::where('payment_number', $number)->exists());

        return $number;
    }
}