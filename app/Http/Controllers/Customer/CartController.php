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
use App\Services\PrintShopRouter;
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

        $items = $cart->items()->with(['product', 'design', 'printFiles'])->get()->map(function (CartItem $item) {
            $options = $item->selected_options ?? [];

            $designImage = null;

            if ($item->item_type === CartItem::TYPE_CUSTOMER_UPLOAD) {
                // The customer's own artwork (private storage) is shown on top of the product picture.
                $artwork = $item->printFiles
                    ->where('status', '!=', PrintFile::STATUS_DELETED)
                    ->first(fn (PrintFile $file) => str_starts_with((string) $file->mime_type, 'image/'));
                $designImage = $artwork && $item->product?->code !== 'PAPER-PRINT'
                    ? route('customer.print-files.preview', $artwork)
                    : null;

                $options['files'] = $item->printFiles
                    ->where('status', '!=', PrintFile::STATUS_DELETED)
                    ->map(fn (PrintFile $file) => [
                        'name' => $file->original_name,
                        'path' => $file->stored_path,
                        'size' => $file->file_size,
                        'page_count' => $file->page_count,
                    ])
                    ->values()
                    ->all();
            }

            // Catalog lines are a design printed on a product, so show the design the customer picked;
            // paper-print lines only carry a placeholder design, so they keep the product's name and image.
            $design = $item->item_type === CartItem::TYPE_CUSTOMER_UPLOAD || $item->product?->code === 'PAPER-PRINT'
                ? null
                : $item->design;

            return [
                'id' => $item->id,
                'product_name' => $design?->title ?: ($options['design_name'] ?? null) ?: ($item->product?->name ?? 'منتج'),
                'design_overlay' => $designImage,
                'mockup' => $options['mockup'] ?? null,
                'product_label' => $design || ! empty($options['design_name']) ? $item->product?->name : null,
                'product_image' => asset($design?->image ?: $item->product?->image ?: 'front/assets/images/customer/products/1.png'),
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

        $cart = $this->activeCart($request);
        $router = app(PrintShopRouter::class);
        $replacing = CartItem::where(['cart_id' => $cart->id, 'product_id' => $product->id, 'variant_id' => $variant->id, 'design_id' => null])->value('id');

        // One shop must make the whole cart, this paper job included.
        $route = $this->routeCart($request, $cart, [$router->need($product, collect([$variant->id]), [], null)], $replacing, 'size');
        $offering = $route['offerings'][$product->id];

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
        $this->applyRoute($cart, $route);

        foreach ($request->file('files') as $file) {
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $pageCount = $this->detectPageCount($file, $extension);
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

        $router = app(PrintShopRouter::class);
        $route = $this->routeCart($request, $cart, collect($validated['groups'])->map(fn ($group) => $router->need(
            $product,
            collect([$this->matchVariant($variants, $group['color_id'], $group['size_id'])->id]),
            $group['print_areas'] ?? [],
            null,
            (int) $group['quantity'],
        ))->all(), null, 'product_code');

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

        $this->applyRoute($cart, $route);

        $request->session()->flash('status', 'item-added');

        return response()->json(['redirect' => route('customer.basket')]);
    }

    /**
     * Add-to-cart for a design the customer made themselves (uploaded image or design studio). There is no
     * published Design row for it, so the line is a "customer_upload" item (design_id null) that carries the
     * artwork files in print_files and the studio layout in selected_options, like paper printing does.
     * Pricing is a placeholder for now: the cheapest active print shop's base price for the product.
     */
    public function storeCustomDesign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_code' => ['required', 'in:TSHIRT-CLASSIC,HOODIE-PREMIUM,MUG-CERAMIC'],
            'design_name' => ['nullable', 'string', 'max:120'],
            'groups' => ['required', 'json'],
            'layout' => ['nullable', 'json', 'max:200000'],
            'files' => ['nullable', 'array', 'max:20'],
            'files.*' => ['file', 'mimes:png,jpg,jpeg,webp,svg', 'max:10240'],
            'file_assets' => ['nullable', 'array', 'max:20'],
            'file_assets.*' => ['string', 'max:120'],
        ]);

        $groups = collect(json_decode($validated['groups'], true) ?: []);
        $groups->each(fn ($group) => validator((array) $group, [
            'color_id' => ['required', 'string', 'max:40'],
            'color_name' => ['nullable', 'string', 'max:40'],
            'size_id' => ['required', 'string', 'max:24'],
            'size_name' => ['nullable', 'string', 'max:24'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'print_areas' => ['nullable', 'array'],
            'print_areas.*' => ['string', 'max:50'],
            'mockup' => ['nullable', 'array'],
        ])->validate());

        if ($groups->isEmpty() || $groups->count() > 99) {
            throw ValidationException::withMessages(['groups' => 'اختر لونًا ومقاسًا وكمية على الأقل.']);
        }

        $product = Product::where('code', $validated['product_code'])->where('is_active', true)->first();
        $variants = $product
            ? Variant::with('values.productAttributeValue.attributeValue', 'values.productAttributeValue.productAttribute.attribute')
                ->where('product_id', $product->id)->where('is_active', true)->orderBy('id')->get()
            : collect();

        if (! $product || $variants->isEmpty()) {
            throw ValidationException::withMessages(['product_code' => 'هذا المنتج غير متاح للطلب حاليًا.']);
        }

        // One shop must make the whole cart: it offers the colours, sizes and print areas of every line and the designs fit.
        // Among those, the customer's own city comes first, then the cheapest.
        $cart = $this->activeCart($request);
        $router = app(PrintShopRouter::class);
        $layoutData = ($validated['layout'] ?? null) ? json_decode($validated['layout'], true) : null;
        $route = $this->routeCart($request, $cart, $groups->map(fn ($group) => $router->need(
            $product,
            collect([$this->matchVariant($variants, $group['color_id'], $group['size_id'])->id]),
            $group['print_areas'] ?? [],
            is_array($layoutData) ? $layoutData : null,
            (int) $group['quantity'],
        ))->all(), null, 'product_code');
        $offering = $route['offerings'][$product->id];
        $user = $request->user();
        $cart = $this->activeCart($request);
        $files = $request->file('files', []);
        $layout = ($validated['layout'] ?? null) ? json_decode($validated['layout'], true) : null;

        foreach ($groups as $group) {
            $variant = $this->matchVariant($variants, $group['color_id'], $group['size_id']);

            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'design_id' => null,
                'item_type' => CartItem::TYPE_CUSTOMER_UPLOAD,
                'quantity' => $group['quantity'],
                'unit_price' => $offering->base_price,
                'selected_options' => array_filter([
                    'color' => $group['color_name'] ?? $group['color_id'],
                    'size' => $group['size_name'] ?? $group['size_id'],
                    'print_areas' => $group['print_areas'] ?? [],
                    'design_name' => $validated['design_name'] ?? null,
                    'layout' => $layout,
                    'branch_product_offering_id' => $offering->id,
                    'print_provider_branch_id' => $offering->print_provider_branch_id,
                ]),
            ]);

            $assetFiles = [];

            // Every line of the same design keeps its own copy of the files, so removing one line never breaks another.
            foreach ($files as $index => $file) {
                $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
                $path = $file->storeAs('customer-print-files/tmp/'.$user->id, Str::uuid().($extension ? '.'.$extension : ''), 'local');

                $saved = PrintFile::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'cart_item_id' => $item->id,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_path' => $path,
                    'disk' => 'local',
                    'mime_type' => $file->getClientMimeType(),
                    'extension' => $extension,
                    'file_size' => $file->getSize(),
                    'page_count' => 1,
                    'status' => PrintFile::STATUS_ATTACHED_TO_CART,
                    'uploaded_at' => now(),
                ]);

                if (isset($validated['file_assets'][$index])) {
                    $assetFiles[$validated['file_assets'][$index]] = route('customer.print-files.preview', $saved, false);
                }
            }

            // How the design looked in the preview, so the cart can redraw it the same way.
            if ($mockup = $this->cleanMockup((array) ($group['mockup'] ?? []), $assetFiles)) {
                $item->update(['selected_options' => array_merge($item->selected_options ?? [], ['mockup' => $mockup])]);
            }
        }

        $this->applyRoute($cart, $route);

        $request->session()->flash('status', 'item-added');

        return response()->json(['redirect' => route('customer.basket')]);
    }

    /**
     * Keeps only what the cart thumbnail needs, with every value checked: the browser is not trusted, and the
     * result is rendered into inline styles and image URLs.
     *
     * @param  array<string, mixed>  $mockup
     * @param  array<string, string>  $assetFiles  studio asset id => preview URL of the saved file
     * @return array<string, mixed>|null
     */
    private function cleanMockup(array $mockup, array $assetFiles): ?array
    {
        $number = fn ($value, float $min = -500, float $max = 500) => max($min, min($max, round((float) $value, 3)));
        $hex = fn ($value) => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : null;
        $localPath = fn ($value, string $prefix) => is_string($value)
            && str_starts_with($value, $prefix)
            && ! str_contains($value, '..')
            && preg_match('#^/[A-Za-z0-9_\-./%]+$#', $value)
            && is_file(public_path(ltrim(rawurldecode($value), '/')))
            ? $value : null;

        $image = $localPath($mockup['image'] ?? null, '/front/') ?? $localPath($mockup['image'] ?? null, '/storage/');
        $zone = (array) ($mockup['zone'] ?? []);

        if (! $image || ! isset($zone['top'], $zone['left'], $zone['width'], $zone['height'])) {
            return null;
        }

        $frame = fn (array $item) => [
            'x' => $number($item['x'] ?? 50), 'y' => $number($item['y'] ?? 50),
            'width' => $number($item['width'] ?? 20, 0), 'height' => $number($item['height'] ?? 20, 0),
            'rotation' => $number($item['rotation'] ?? 0, -360, 360),
            'flip_x' => (bool) ($item['flipX'] ?? false), 'flip_y' => (bool) ($item['flipY'] ?? false),
            'layer' => (int) $number($item['layer'] ?? 1, 1, 999),
        ];

        $images = collect($mockup['images'] ?? [])->take(40)->map(function ($item) use ($assetFiles, $frame, $hex, $localPath) {
            $item = (array) $item;
            $src = isset($item['asset_id']) ? ($assetFiles[$item['asset_id']] ?? null) : $localPath($item['src'] ?? null, '/front/studio/assets/images/studio-graphics/');

            return $src ? $frame($item) + ['src' => $src, 'tint' => $hex($item['tint'] ?? null)] : null;
        })->filter()->values()->all();

        $texts = collect($mockup['texts'] ?? [])->take(20)->map(function ($item) use ($frame, $hex, $number) {
            $item = (array) $item;
            $family = preg_replace('/[^\p{L}\p{N} \-_]/u', '', (string) ($item['font_family'] ?? 'Cairo')) ?: 'Cairo';

            return $frame($item) + [
                'content' => mb_substr(strip_tags((string) ($item['content'] ?? '')), 0, 200),
                'size_percent' => $number($item['size_percent'] ?? 10, 1, 100),
                'font_family' => $family,
                'color' => $hex($item['color'] ?? null) ?? '#0b1f3a',
                'font_weight' => in_array($item['font_weight'] ?? '', ['bold', '700', '800'], true) ? 'bold' : 'normal',
                'font_style' => ($item['font_style'] ?? '') === 'italic' ? 'italic' : 'normal',
                'text_align' => in_array($item['text_align'] ?? '', ['left', 'right', 'center'], true) ? $item['text_align'] : 'center',
                'line_height' => $number($item['line_height'] ?? 1.2, 0.8, 3),
            ];
        })->filter(fn ($text) => $text['content'] !== '')->values()->all();

        return [
            'image' => $image,
            'tint' => $hex($mockup['tint'] ?? null),
            'area_name' => mb_substr((string) ($mockup['areaName'] ?? ''), 0, 50),
            'zone' => ['top' => $number($zone['top'], 0, 100), 'left' => $number($zone['left'], 0, 100), 'width' => $number($zone['width'], 0, 100), 'height' => $number($zone['height'], 0, 100)],
            'images' => $images,
            'texts' => $texts,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Variant>  $variants
     */
    /**
     * The shop that will make the whole cart: the lines already in it plus the new ones. When no single shop can make it all
     * the new line is refused, so an order never ends up with a shop that cannot fulfil part of it.
     *
     * @param  array<int, array>  $newNeeds
     * @return array{branchId: int, offerings: array<int, BranchProductOffering>, estimate: float}
     */
    private function routeCart(Request $request, Cart $cart, array $newNeeds, ?int $replacingItemId, string $errorField): array
    {
        $router = app(PrintShopRouter::class);
        $current = $cart->items()->with('product')->get()
            ->filter(fn (CartItem $item) => $item->product && $item->id !== $replacingItemId)
            ->map(fn (CartItem $item) => $router->needFromCartItem($item));

        $route = $router->chooseForOrder($current->concat($newNeeds)->values(), $router->customerCity($request->user()));

        if (! $route) {
            throw ValidationException::withMessages([$errorField => $current->isEmpty()
                ? 'لا توجد مطبعة متاحة تقدم مناطق الطباعة والألوان والمقاسات التي اخترتها بهذا الحجم حاليًا.'
                : 'لا توجد مطبعة واحدة تستطيع تنفيذ كل منتجات سلتك مع هذا المنتج. أكمل طلب السلة الحالية أولًا ثم اطلب هذا المنتج في طلب منفصل.']);
        }

        return $route;
    }

    /** Lines that remember their shop (paper and the customer's own designs) follow the shop chosen for the whole cart. */
    private function applyRoute(Cart $cart, array $route): void
    {
        foreach ($cart->items()->get() as $item) {
            $options = $item->selected_options ?? [];
            $offering = $route['offerings'][$item->product_id] ?? null;

            if ($offering && array_key_exists('branch_product_offering_id', $options)) {
                $options['branch_product_offering_id'] = $offering->id;
                $options['print_provider_branch_id'] = $offering->print_provider_branch_id;
                $item->update(['selected_options' => $options]);
            }
        }
    }

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

    /** Shows a customer's own uploaded image (private storage) to its owner, for the cart thumbnail. */
    public function printFilePreview(Request $request, PrintFile $printFile)
    {
        abort_unless($printFile->user_id === $request->user()->id, 403);
        abort_unless(str_starts_with((string) $printFile->mime_type, 'image/') && $printFile->status !== PrintFile::STATUS_DELETED, 404);

        $disk = Storage::disk($printFile->disk ?: 'local');
        abort_unless($disk->exists($printFile->stored_path), 404);

        return response()->file($disk->path($printFile->stored_path), [
            'Content-Type' => $printFile->mime_type,
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'", // keeps an uploaded SVG from running scripts
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
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
