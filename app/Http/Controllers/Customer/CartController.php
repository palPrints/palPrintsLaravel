<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Design;
use App\Models\Product;
use App\Models\Variant;
use App\Support\CatalogProductData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $this->activeCart($request);

        $items = $cart->items()->with('product')->get()->map(fn (CartItem $item) => [
            'id' => $item->id,
            'product_name' => $item->product?->name ?? 'منتج',
            'product_image' => asset($item->product?->image ?: 'front/assets/images/customer/products/1.png'),
            'quantity' => $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'total_price' => (float) $item->unit_price * $item->quantity,
            'options' => $item->selected_options ?? [],
        ]);

        return view('customer.basket', [
            'items' => $items,
            'subtotal' => $items->sum('total_price'),
        ]);
    }

    /**
     * Paper printing has no real "design" to pick — it's a customer file
     * upload, not a catalog design — so cart_items.design_id (required FK)
     * is satisfied with a placeholder Design row created for product
     * PAPER-PRINT (see the conversation with the data team about eventually
     * making design_id nullable for upload-based products). Each print job
     * becomes ONE cart line with quantity=1 and unit_price = the full job
     * total already computed client-side by the pricing engine in
     * paperPrinting.js — the +/- stepper on the basket page then just
     * multiplies whole extra copies of the job, not individual pages.
     */
    public function storePaper(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'size' => ['required', 'in:A4,A5'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'settings' => ['nullable', 'array'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,docx,pptx', 'max:51200'],
        ]);

        $product = Product::where('code', 'PAPER-PRINT')->firstOrFail();

        $variant = Variant::where('product_id', $product->id)
            ->where('sku', 'like', '%-'.$validated['size'])
            ->first();

        $design = Design::where('product_id', $product->id)->where('status', 'published')->first();

        if (! $variant || ! $design) {
            throw ValidationException::withMessages([
                'size' => 'حجم الورق غير متاح حاليًا، حاول لاحقًا.',
            ]);
        }

        $cart = $this->activeCart($request);

        $files = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store('customer/paper-uploads/'.$request->user()->id, 'public');
            $files[] = ['name' => $file->getClientOriginalName(), 'path' => $path];
        }

        CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'design_id' => $design->id,
            ],
            [
                'quantity' => 1,
                'unit_price' => $validated['total_price'],
                'selected_options' => array_merge($validated['settings'] ?? [], ['files' => $files]),
            ]
        );

        return redirect()->route('customer.basket')->with('status', 'item-added');
    }

    /**
     * Add-to-cart for the catalog products that go through the shared
     * productPreview page (t-shirts, hoodies, mugs). The browser only says
     * WHAT was picked (product code, design, color/size ids, quantity);
     * the product, design, variant and price are all resolved here so the
     * client can't set its own price.
     *
     * The preview page's colors/sizes are not guaranteed to exist as real
     * variants yet (e.g. the t-shirt preview offers XXL, the catalog only
     * has S-XL), so the variant FK falls back to the closest active variant
     * while the customer's actual choice is always kept in selected_options.
     */
    public function storeCatalog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_code' => ['required', 'in:TSHIRT-CLASSIC,HOODIE-PREMIUM,MUG-CERAMIC'],
            'design_id' => ['required', 'integer'],
            'groups' => ['required', 'array', 'min:1', 'max:99'],
            'groups.*.color_id' => ['required', 'string', 'max:40'],
            'groups.*.color_name' => ['nullable', 'string', 'max:40'],
            'groups.*.size_id' => ['required', 'string', 'max:24'],
            'groups.*.size_name' => ['nullable', 'string', 'max:24'],
            'groups.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'groups.*.print_areas' => ['nullable', 'array'],
            'groups.*.print_areas.*' => ['string', 'max:50'],
        ]);

        $product = Product::where('code', $validated['product_code'])->where('is_active', true)->first();

        $design = $product
            ? Design::where('product_id', $product->id)
                ->where('status', 'published')
                ->find($validated['design_id'])
            : null;

        if (! $product || ! $design) {
            throw ValidationException::withMessages([
                'product_code' => 'هذا المنتج غير متاح للطلب حاليًا.',
            ]);
        }

        $variants = Variant::with('values.productAttributeValue.attributeValue', 'values.productAttributeValue.productAttribute.attribute')
            ->where('product_id', $product->id)->where('is_active', true)->orderBy('id')->get();
        if ($variants->isEmpty()) {
            throw ValidationException::withMessages([
                'product_code' => 'لا توجد خيارات متاحة لهذا المنتج حاليًا.',
            ]);
        }

        $unitPrice = (float) ($design->selling_price ?: $design->base_price);
        $cart = $this->activeCart($request);

        foreach ($validated['groups'] as $group) {
            $variant = $this->matchVariant($variants, $group['color_id'], $group['size_id']);

            $options = array_filter([
                'color' => $group['color_name'] ?? $group['color_id'],
                'size' => $group['size_name'] ?? $group['size_id'],
                'print_areas' => $group['print_areas'] ?? [],
            ]);

            $item = CartItem::firstOrNew([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'design_id' => $design->id,
            ]);

            // UNIQUE(cart_id, product_id, variant_id, design_id): same choice
            // again just adds to the quantity; a different choice that maps
            // to the same variant replaces the line, like the paper flow does.
            $sameChoice = $item->exists && ($item->selected_options ?? []) == $options;
            $item->quantity = min(99, ($sameChoice ? $item->quantity : 0) + $group['quantity']);
            $item->unit_price = $unitPrice;
            $item->selected_options = $options;
            $item->save();
        }

        $request->session()->flash('status', 'item-added');

        return response()->json(['redirect' => route('customer.basket')]);
    }

    /**
     * Matches by the variant's real color/size attribute values (through
     * variant_values), not by SKU text, because the catalog holds both
     * "tshirt-white" and "white" style codes for the same choice. Falls back
     * to a size-only, then color-only, then any active variant.
     *
     * @param  \Illuminate\Support\Collection<int, Variant>  $variants
     */
    private function matchVariant($variants, string $colorId, string $sizeId): Variant
    {
        $color = CatalogProductData::normalizeCode($colorId);
        $size = CatalogProductData::normalizeCode($sizeId);

        $codeOf = fn (Variant $variant, string $attribute) => $variant->values
            ->map(fn ($value) => $value->productAttributeValue)
            ->first(fn ($pav) => $pav?->productAttribute?->attribute?->code === $attribute)
            ?->attributeValue?->code;

        $hasColor = fn (Variant $v) => ($c = $codeOf($v, 'color')) !== null && CatalogProductData::normalizeCode($c) === $color;
        $hasSize = fn (Variant $v) => ($c = $codeOf($v, 'size')) !== null && CatalogProductData::normalizeCode($c) === $size;

        return $variants->first(fn (Variant $v) => $hasColor($v) && $hasSize($v))
            ?? $variants->first($hasSize)
            ?? $variants->first($hasColor)
            ?? $variants->first();
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeItem($request, $cartItem);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cartItem->update(['quantity' => $validated['quantity']]);

        return back()->with('status', 'cart-updated');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeItem($request, $cartItem);

        foreach (($cartItem->selected_options['files'] ?? []) as $file) {
            if (! empty($file['path'])) {
                Storage::disk('public')->delete($file['path']);
            }
        }

        $cartItem->delete();

        return back()->with('status', 'item-removed');
    }

    private function activeCart(Request $request): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $request->user()->id, 'status' => 'active'],
        );
    }

    private function authorizeItem(Request $request, CartItem $cartItem): void
    {
        abort_unless($cartItem->cart->user_id === $request->user()->id, 403);
    }
}
