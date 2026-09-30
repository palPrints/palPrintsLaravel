<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Design;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\Variant;
use App\Support\CatalogProductData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $this->activeCart($request);

        $items = $cart->items()->with(['product', 'printFiles'])->get()->map(function (CartItem $item) {
            $options = $item->selected_options ?? [];

            if ($item->item_type === CartItem::TYPE_CUSTOMER_UPLOAD) {
                $options['files'] = $item->printFiles
                    ->where('status', '!=', PrintFile::STATUS_DELETED)
                    ->map(fn (PrintFile $file) => [
                        'name' => $file->original_name,
                        'path' => $file->stored_path,
                        'size' => $file->file_size,
                    ])
                    ->values()
                    ->all();
            }

            return [
                'id' => $item->id,
                'product_name' => $item->product?->name ?? 'منتج',
                'product_image' => asset($item->product?->image ?: 'front/assets/images/customer/products/1.png'),
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->unit_price * $item->quantity,
                'options' => $options,
            ];
        });

        return view('customer.basket', [
            'items' => $items,
            'subtotal' => $items->sum('total_price'),
        ]);
    }

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

        if (! $variant) {
            throw ValidationException::withMessages([
                'size' => 'حجم الورق غير متاح حالياً، حاول لاحقاً.',
            ]);
        }

        $cart = $this->activeCart($request);

        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'design_id' => null,
        ]);

        if ($item->exists) {
            $this->deletePaperFilesForItem($item);
        }

        $settings = array_merge($validated['settings'] ?? [], [
            'paper_size' => $validated['size'],
        ]);

        $item->fill([
            'item_type' => CartItem::TYPE_CUSTOMER_UPLOAD,
            'quantity' => 1,
            'unit_price' => $validated['total_price'],
            'selected_options' => $settings,
        ]);
        $item->save();

        foreach ($request->file('files') as $file) {
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $filename = (string) Str::uuid().($extension ? '.'.$extension : '');
            $path = $file->storeAs('customer-print-files/tmp/'.$request->user()->id, $filename, 'local');

            PrintFile::create([
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
                'cart_item_id' => $item->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'disk' => 'local',
                'mime_type' => $file->getClientMimeType(),
                'extension' => $extension,
                'file_size' => $file->getSize(),
                'status' => PrintFile::STATUS_ATTACHED_TO_CART,
                'uploaded_at' => now(),
            ]);
        }

        return redirect()->route('customer.basket')->with('status', 'item-added');
    }

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
                'product_code' => 'هذا المنتج غير متاح للطلب حالياً.',
            ]);
        }

        $variants = Variant::with('values.productAttributeValue.attributeValue', 'values.productAttributeValue.productAttribute.attribute')
            ->where('product_id', $product->id)->where('is_active', true)->orderBy('id')->get();
        if ($variants->isEmpty()) {
            throw ValidationException::withMessages([
                'product_code' => 'لا توجد خيارات متاحة لهذا المنتج حالياً.',
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

            $sameChoice = $item->exists && ($item->selected_options ?? []) == $options;
            $item->item_type = CartItem::TYPE_CATALOG_DESIGN;
            $item->quantity = min(99, ($sameChoice ? $item->quantity : 0) + $group['quantity']);
            $item->unit_price = $unitPrice;
            $item->selected_options = $options;
            $item->save();
        }

        $request->session()->flash('status', 'item-added');

        return response()->json(['redirect' => route('customer.basket')]);
    }

    /**
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

        $this->deletePaperFilesForItem($cartItem);

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

    private function deletePaperFilesForItem(CartItem $cartItem): void
    {
        $cartItem->loadMissing('printFiles');

        foreach ($cartItem->printFiles as $file) {
            if ($file->stored_path) {
                Storage::disk($file->disk ?: 'local')->delete($file->stored_path);
            }

            $file->update([
                'status' => PrintFile::STATUS_DELETED,
                'deleted_at' => now(),
            ]);
        }

        foreach (($cartItem->selected_options['files'] ?? []) as $file) {
            if (! empty($file['path'])) {
                Storage::disk('public')->delete($file['path']);
            }
        }
    }
}
