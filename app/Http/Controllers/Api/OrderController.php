<?php

namespace App\Http\Controllers\Api;

use App\Models\Address;
use App\Models\DesignProduct;
use App\Models\Order;
use App\Models\PrintProvider;
use App\Models\PrintProviderProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in($this->statuses())],
            'payment_status' => ['nullable', Rule::in($this->paymentStatuses())],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $orders = Order::query()->with($this->relations());

        $this->scopeVisibleOrders($orders, $user);

        $orders
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['payment_status'] ?? null, fn ($query, string $status) => $query->where('payment_status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%");
                });
            });

        return $this->success(
            $orders->latest()->paginate($validated['per_page'] ?? 15),
            'تم جلب الطلبات بنجاح.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'customer' || ! $this->hasPermission($user, 'create orders')) {
            return $this->error('إنشاء الطلبات متاح للعملاء فقط.', 403);
        }

        $validated = $request->validate([
            'shipping_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'print_provider_id' => ['nullable', 'integer', 'exists:print_providers,id'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.design_product_id' => ['required', 'integer', 'exists:design_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.selected_color' => ['nullable', 'string', 'max:100'],
            'items.*.selected_size' => ['nullable', 'string', 'max:100'],
        ]);

        $address = Address::whereKey($validated['shipping_address_id'])
            ->where('user_id', $user->id)
            ->where('is_deleted', false)
            ->first();

        if (! $address) {
            return $this->error('عنوان الشحن غير موجود أو لا يتبع حسابك.', 422);
        }

        $provider = null;

        if (! empty($validated['print_provider_id'])) {
            $provider = PrintProvider::query()
                ->whereKey($validated['print_provider_id'])
                ->where('approval_status', 'approved')
                ->where('is_active', true)
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->first();

            if (! $provider) {
                return $this->error('المطبعة المحددة غير متاحة حاليًا.', 422);
            }
        }

        $order = DB::transaction(function () use ($user, $validated, $provider): Order {
            $items = [];
            $totalAmount = 0.0;

            foreach ($validated['items'] as $item) {
                $designProduct = DesignProduct::query()
                    ->with(['design', 'product'])
                    ->lockForUpdate()
                    ->findOrFail($item['design_product_id']);

                if (
                    ! $designProduct->is_active
                    || ! $designProduct->product->is_active
                    || $designProduct->design->status !== 'published'
                ) {
                    throw ValidationException::withMessages([
                        'items' => ['أحد التصاميم أو المنتجات المحددة غير متاح للطلب.'],
                    ]);
                }

                $productionPrice = (float) $designProduct->product->base_price;

                if ($provider) {
                    $providerProduct = PrintProviderProduct::query()
                        ->where('print_provider_id', $provider->id)
                        ->where('product_id', $designProduct->product_id)
                        ->where('is_active', true)
                        ->first();

                    if (! $providerProduct) {
                        throw ValidationException::withMessages([
                            'print_provider_id' => ['المطبعة المحددة لا توفر أحد المنتجات الموجودة في الطلب.'],
                        ]);
                    }

                    $productionPrice = (float) $providerProduct->price;
                    $this->validateVariant($providerProduct, $item);
                }

                $designerUnitEarnings = round(
                    (float) $designProduct->design->base_price
                    + (float) $designProduct->designer_margin,
                    2
                );
                $unitPrice = round($productionPrice + $designerUnitEarnings, 2);
                $quantity = (int) $item['quantity'];
                $totalPrice = round($unitPrice * $quantity, 2);
                $totalAmount = round($totalAmount + $totalPrice, 2);

                $items[] = [
                    'design_product_id' => $designProduct->id,
                    'quantity' => $quantity,
                    'selected_color' => $item['selected_color'] ?? null,
                    'selected_size' => $item['selected_size'] ?? null,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'designer_earnings' => round($designerUnitEarnings * $quantity, 2),
                    'print_provider_earnings' => round($productionPrice * $quantity, 2),
                    // تُحدّث لاحقًا عند اعتماد سياسة عمولة المنصة.
                    'platform_commission' => 0,
                ];
            }

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->newOrderNumber(),
                'total_amount' => $totalAmount,
                'shipping_cost' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'final_amount' => $totalAmount,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $validated['payment_method'] ?? null,
                'shipping_address_id' => $validated['shipping_address_id'],
                'print_provider_id' => $provider?->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->orderItems()->createMany($items);

            return $order;
        });

        return $this->success(
            $order->load($this->relations()),
            'تم إنشاء الطلب بنجاح.',
            201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::with($this->relations())->findOrFail($id);

        if (! $this->canViewOrder($request->user(), $order)) {
            return $this->error('ليس لديك صلاحية لعرض هذا الطلب.', 403);
        }

        return $this->success($order, 'تم جلب الطلب بنجاح.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        if (! $isAdmin && ! $this->canManageOrder($user, $order)) {
            return $this->error('ليس لديك صلاحية لتعديل هذا الطلب.', 403);
        }

        $rules = $this->updateRulesFor($user, $order, $isAdmin);
        $validated = $request->validate($rules);

        if (! $isAdmin && $user->role === 'customer') {
            if (! in_array($order->status, ['pending', 'processing'], true)) {
                return $this->error('لا يمكن إلغاء الطلب بعد بدء الطباعة أو الشحن.', 422);
            }

            $validated = [
                'status' => 'cancelled',
                'cancellation_reason' => $validated['cancellation_reason'],
            ];
        }

        if (isset($validated['print_provider_id'])) {
            $this->ensureApprovedProvider($validated['print_provider_id']);
        }

        if (isset($validated['delivery_partner_id'])) {
            $deliveryPartner = User::whereKey($validated['delivery_partner_id'])
                ->where('role', 'delivery_partner')
                ->where('is_active', true)
                ->first();

            if (! $deliveryPartner) {
                throw ValidationException::withMessages([
                    'delivery_partner_id' => ['مندوب التوصيل المحدد غير صالح أو غير مفعّل.'],
                ]);
            }
        }

        if (($validated['status'] ?? null) === 'delivered') {
            $validated['delivered_at'] = now();
        }

        if (($validated['status'] ?? null) === 'cancelled') {
            $validated['cancelled_at'] = now();
        }

        $order->update($validated);

        return $this->success(
            $order->fresh()->load($this->relations()),
            'تم تعديل الطلب بنجاح.'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $this->isAdmin($request->user())) {
            return $this->error('حذف الطلبات متاح للإدارة فقط.', 403);
        }

        $order = Order::findOrFail($id);

        if ($order->payment_status === 'paid') {
            return $this->error('لا يمكن حذف طلب مدفوع. استخدم الإلغاء أو الاسترجاع بدلًا من ذلك.', 409);
        }

        if (! in_array($order->status, ['pending', 'cancelled'], true)) {
            return $this->error('لا يمكن حذف طلب بدأ تنفيذه.', 409);
        }

        $order->delete();

        return $this->success(null, 'تم حذف الطلب بنجاح.');
    }

    private function scopeVisibleOrders(Builder $query, User $user): void
    {
        if ($this->isAdmin($user)) {
            return;
        }

        match ($user->role) {
            'customer' => $query->where('user_id', $user->id),
            'designer' => $query->whereHas(
                'orderItems.designProduct.design',
                fn ($query) => $query->where('designer_id', $user->id)
            ),
            'print_provider' => $query->where(
                'print_provider_id',
                $user->printProvider?->id ?? 0
            ),
            'delivery_partner' => $query->where('delivery_partner_id', $user->id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function canViewOrder(User $user, Order $order): bool
    {
        if ($this->isAdmin($user) || $order->user_id === $user->id) {
            return true;
        }

        if ($user->role === 'print_provider' && $order->print_provider_id === $user->printProvider?->id) {
            return true;
        }

        if ($user->role === 'delivery_partner' && $order->delivery_partner_id === $user->id) {
            return true;
        }

        return $user->role === 'designer'
            && $order->orderItems()
                ->whereHas(
                    'designProduct.design',
                    fn ($query) => $query->where('designer_id', $user->id)
                )
                ->exists();
    }

    private function canManageOrder(User $user, Order $order): bool
    {
        if ($user->role === 'customer') {
            return $order->user_id === $user->id;
        }

        if ($user->role === 'print_provider') {
            return $order->print_provider_id === $user->printProvider?->id
                && $this->hasPermission($user, 'manage orders');
        }

        return $user->role === 'delivery_partner' && $order->delivery_partner_id === $user->id;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function updateRulesFor(User $user, Order $order, bool $isAdmin): array
    {
        if ($isAdmin) {
            return [
                'status' => ['sometimes', Rule::in($this->statuses())],
                'payment_status' => ['sometimes', Rule::in($this->paymentStatuses())],
                'payment_method' => ['sometimes', 'nullable', 'string', 'max:100'],
                'payment_transaction_id' => ['sometimes', 'nullable', 'string', 'max:255'],
                'print_provider_id' => ['sometimes', 'nullable', 'integer', 'exists:print_providers,id'],
                'delivery_partner_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
                'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
                'estimated_delivery_date' => ['sometimes', 'nullable', 'date'],
                'cancellation_reason' => ['sometimes', 'nullable', 'string', 'max:3000'],
                'notes' => ['sometimes', 'nullable', 'string', 'max:3000'],
            ];
        }

        if ($user->role === 'customer') {
            return [
                'status' => ['required', Rule::in(['cancelled'])],
                'cancellation_reason' => ['required', 'string', 'max:3000'],
            ];
        }

        if ($user->role === 'print_provider') {
            return [
                'status' => ['sometimes', Rule::in(['processing', 'printing', 'shipped'])],
                'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
                'estimated_delivery_date' => ['sometimes', 'nullable', 'date'],
                'notes' => ['sometimes', 'nullable', 'string', 'max:3000'],
            ];
        }

        return [
            'status' => ['required', Rule::in(['shipped', 'delivered'])],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    private function ensureApprovedProvider(int $providerId): void
    {
        $exists = PrintProvider::query()
            ->whereKey($providerId)
            ->where('approval_status', 'approved')
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'print_provider_id' => ['المطبعة المحددة غير مفعّلة أو غير معتمدة.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function validateVariant(PrintProviderProduct $providerProduct, array $item): void
    {
        if (
            ! empty($item['selected_color'])
            && is_array($providerProduct->available_colors)
            && ! in_array($item['selected_color'], $providerProduct->available_colors, true)
        ) {
            throw ValidationException::withMessages([
                'items' => ['اللون المحدد غير متاح لدى المطبعة.'],
            ]);
        }

        if (
            ! empty($item['selected_size'])
            && is_array($providerProduct->available_sizes)
            && ! in_array($item['selected_size'], $providerProduct->available_sizes, true)
        ) {
            throw ValidationException::withMessages([
                'items' => ['المقاس المحدد غير متاح لدى المطبعة.'],
            ]);
        }
    }

    private function newOrderNumber(): string
    {
        do {
            $number = 'PAL-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * @return list<string>
     */
    private function statuses(): array
    {
        return ['pending', 'processing', 'printing', 'shipped', 'delivered', 'cancelled', 'refunded'];
    }

    /**
     * @return list<string>
     */
    private function paymentStatuses(): array
    {
        return ['pending', 'paid', 'failed', 'refunded'];
    }

    /**
     * @return list<string>
     */
    private function relations(): array
    {
        return [
            'user:id,name,email,phone',
            'shippingAddress',
            'printProvider.user:id,name,email,phone',
            'deliveryPartner:id,name,email,phone',
            'orderItems.designProduct.design.designer:id,name',
            'orderItems.designProduct.product',
        ];
    }
}
