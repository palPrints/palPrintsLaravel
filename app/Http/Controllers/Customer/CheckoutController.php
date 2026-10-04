<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PrintFile;
use App\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->activeCart($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('customer.basket')->with('status', 'cart-empty');
        }

        $items = $cart->items->map(fn (CartItem $item) => $this->presentItem($item));
        $subtotal = $items->sum('total_price');
        $shippingCost = $subtotal > 0 ? 5.00 : 0.00;
        $totalAmount = $subtotal + $shippingCost;
        $addresses = $request->user()->addresses()
            ->where('is_deleted', false)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return view('customer.checkout', [
            'customer' => $request->user(),
            'cart' => $cart,
            'items' => $items,
            'addresses' => $addresses,
            'subtotal' => $subtotal,
            'shippingCost' => $shippingCost,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function store(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^05[69][0-9]{7}$/'],
            'address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where('user_id', $request->user()->id)->where('is_deleted', false),
            ],
            'city' => ['required_without:address_id', 'nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'street' => ['required_without:address_id', 'nullable', 'string', 'max:180'],
            'building' => ['nullable', 'string', 'max:120'],
            'apartment' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['required', 'string', 'in:palpay,jawwal,bank'],
            'payment_receipt' => ['required_if:payment_method,palpay,jawwal,bank', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->hasFile('payment_receipt')) {
            $extension = strtolower($request->file('payment_receipt')->getClientOriginalExtension() ?: 'jpg');
            $validated['payment_receipt_path'] = $request->file('payment_receipt')->storeAs(
                'payment-receipts/'.$request->user()->id,
                Str::uuid().'.'.$extension,
                'local'
            );
            $validated['payment_receipt_disk'] = 'local';
            $validated['payment_receipt_original_name'] = $request->file('payment_receipt')->getClientOriginalName();
        }

        $order = $checkout->completeDemoPayment($request->user(), $validated);

        return redirect()
            ->route('customer.orders')
            ->with('status', 'checkout-complete')
            ->with('order_number', $order->order_number);
    }

    private function activeCart(Request $request): ?Cart
    {
        return Cart::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with([
                'items.product',
                'items.variant',
                'items.design',
                'items.printFiles' => fn ($query) => $query->where('status', '!=', PrintFile::STATUS_DELETED),
            ])
            ->first();
    }

    private function presentItem(CartItem $item): array
    {
        $options = $item->selected_options ?? [];

        if ($item->item_type === CartItem::TYPE_CUSTOMER_UPLOAD) {
            $options['files'] = $item->printFiles->map(fn (PrintFile $file) => [
                'name' => $file->original_name,
                'preview_url' => $file->preview_path ? route('customer.print-files.preview', $file) : null,
                'size' => $file->file_size,
                'page_count' => $file->page_count,
            ])->values()->all();
        }

        $firstPreview = collect($options['files'] ?? [])->firstWhere('preview_url');

        return [
            'id' => $item->id,
            'item_type' => $item->item_type,
            'product_name' => $item->product?->name ?? 'منتج',
            'product_image' => $firstPreview['preview_url'] ?? asset($item->product?->image ?: 'front/assets/images/customer/products/1.png'),
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'total_price' => (float) $item->unit_price * (int) $item->quantity,
            'options' => $options,
            'option_tags' => $this->paperOptionTags($options),
        ];
    }
    private function paperOptionTags(array $options): array
    {
        $labels = [
            'paper_size' => 'حجم الورق',
            'paper_type' => 'نوع الورق',
            'color_mode' => 'لون الطباعة',
            'sides' => 'جوانب الطباعة',
            'layout' => 'تخطيط الصفحة',
            'grouping' => 'طريقة الملفات',
            'binding' => 'التغليف',
            'quantity' => 'الكمية',
            'file_count' => 'عدد الملفات',
            'page_count' => 'عدد الصفحات',
        ];

        $values = [
            'standard' => 'عادي 80 جم',
            'thick' => 'فاخر 120 جم',
            'coated' => 'مصقول 150 جم',
            'bw' => 'أبيض وأسود',
            'color' => 'ملون',
            'single' => 'وجه واحد',
            'double' => 'وجهين',
            '1' => 'صفحة واحدة لكل وجه',
            '2' => 'صفحتان لكل وجه',
            '4' => '4 صفحات لكل وجه',
            'combined' => 'دمج الملفات',
            'separate' => 'فصل الملفات',
            'none' => 'بدون تغليف',
        ];

        return collect($labels)
            ->map(function (string $label, string $key) use ($options, $values) {
                if (! array_key_exists($key, $options) || $options[$key] === '' || $options[$key] === null) {
                    return null;
                }

                $value = $options[$key];
                if (is_array($value)) {
                    $value = implode('، ', array_map(fn ($item) => $values[(string) $item] ?? (string) $item, $value));
                } else {
                    $value = $values[(string) $value] ?? (string) $value;
                }

                return $label.': '.$value;
            })
            ->filter()
            ->values()
            ->all();
    }
}