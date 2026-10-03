<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Design;
use App\Models\BranchProductOffering;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\Variant;
use App\Support\CatalogProductData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

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
                        'preview_url' => $file->preview_path ? route('customer.print-files.preview', $file) : null,
                        'size' => $file->file_size,
                        'page_count' => $file->page_count,
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

        $product = Product::where('code', 'PAPER-PRINT')->where('is_active', true)->firstOrFail();

        $variant = Variant::where('product_id', $product->id)
            ->where('sku', 'like', '%-'.$validated['size'])
            ->first();

        if (! $variant) {
            throw ValidationException::withMessages([
                'size' => 'حجم الورق غير متاح حالياً، حاول لاحقاً.',
            ]);
        }

        $offering = BranchProductOffering::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->whereHas('printProviderBranch', fn ($query) => $query->where('is_active', true))
            ->whereHas('branchOfferingVariants', fn ($query) => $query
                ->where('variant_id', $variant->id)
                ->where('is_available', true))
            ->orderBy('base_price')
            ->first();

        if (! $offering) {
            throw ValidationException::withMessages([
                'size' => 'لا يوجد فرع مطبعة متاح لهذا الحجم حالياً.',
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
            'branch_product_offering_id' => $offering->id,
            'print_provider_branch_id' => $offering->print_provider_branch_id,
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
            $pageCount = $this->detectPageCount($file, $extension);
            $filename = (string) Str::uuid().($extension ? '.'.$extension : '');
            $path = $file->storeAs('customer-print-files/tmp/'.$request->user()->id, $filename, 'local');
            $preview = $this->createPreview($file, $path, $extension, $request->user()->id);

            PrintFile::create([
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
                'cart_item_id' => $item->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'preview_path' => $preview['path'],
                'preview_disk' => $preview['disk'],
                'disk' => 'local',
                'mime_type' => $file->getClientMimeType(),
                'extension' => $extension,
                'file_size' => $file->getSize(),
                'page_count' => $pageCount,
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
    public function previewPrintFile(Request $request, PrintFile $printFile): BinaryFileResponse
    {
        abort_unless($printFile->user_id === $request->user()->id, 403);
        abort_unless($printFile->preview_path && $printFile->status !== PrintFile::STATUS_DELETED, 404);

        $disk = $printFile->preview_disk ?: $printFile->disk ?: 'local';
        abort_unless(Storage::disk($disk)->exists($printFile->preview_path), 404);

        $path = Storage::disk($disk)->path($printFile->preview_path);
        $mime = Storage::disk($disk)->mimeType($printFile->preview_path) ?: 'image/jpeg';

        return response()->file($path, ['Content-Type' => $mime]);
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

    private function createPreview(UploadedFile $file, string $storedPath, string $extension, int $userId): array
    {
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return ['path' => $storedPath, 'disk' => 'local'];
        }

        if ($extension !== 'pdf' || ! class_exists(\Imagick::class) || ! $file->getRealPath()) {
            return ['path' => null, 'disk' => null];
        }

        try {
            $previewPath = 'customer-print-files/previews/'.$userId.'/'.Str::uuid().'.jpg';
            $image = new \Imagick($file->getRealPath().'[0]');
            $image->setImageBackgroundColor('white');
            $image->setImageFormat('jpg');
            $image->setImageCompressionQuality(85);
            Storage::disk('local')->put($previewPath, $image->getImagesBlob());
            $image->clear();
            $image->destroy();

            return ['path' => $previewPath, 'disk' => 'local'];
        } catch (\Throwable) {
            return ['path' => null, 'disk' => null];
        }
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

    private function detectPageCount(UploadedFile $file, string $extension): ?int
    {
        return match ($extension) {
            'jpg', 'jpeg', 'png' => 1,
            'pdf' => $this->countPdfPages($file->getRealPath()),
            'pptx' => $this->countPresentationSlides($file->getRealPath()),
            'docx' => $this->countDocumentPages($file->getRealPath()),
            default => null,
        };
    }

    private function countPdfPages(string|false $path): ?int
    {
        if (! $path || ! is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        preg_match_all('/\/Type\s*\/Page\b/', $contents, $matches);

        return count($matches[0]) ?: null;
    }

    private function countPresentationSlides(string|false $path): ?int
    {
        if (! $path || ! class_exists(ZipArchive::class)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && preg_match('#^ppt/slides/slide\d+\.xml$#', $name)) {
                $count++;
            }
        }
        $zip->close();

        return $count ?: null;
    }

    private function countDocumentPages(string|false $path): ?int
    {
        if (! $path || ! class_exists(ZipArchive::class)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        $appXml = $zip->getFromName('docProps/app.xml');
        $zip->close();

        if (! is_string($appXml)) {
            return null;
        }

        return preg_match('/<Pages>(\d+)<\/Pages>/', $appXml, $matches)
            ? (int) $matches[1]
            : null;
    }
    private function deletePaperFilesForItem(CartItem $cartItem): void
    {
        $cartItem->loadMissing('printFiles');

        foreach ($cartItem->printFiles as $file) {
            if ($file->stored_path) {
                Storage::disk($file->disk ?: 'local')->delete($file->stored_path);
            }

            if ($file->preview_path && $file->preview_path !== $file->stored_path) {
                Storage::disk($file->preview_disk ?: $file->disk ?: 'local')->delete($file->preview_path);
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
