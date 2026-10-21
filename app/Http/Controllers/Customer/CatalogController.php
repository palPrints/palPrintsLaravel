<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\DesignFavorite;
use App\Models\Product;
use App\Models\BranchProductOffering;
use App\Models\StickerCategory;
use App\Services\Stickers\StickerSettingsService;
use App\Support\CatalogProductData;
use App\Services\PaperPricing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class CatalogController extends Controller
{
    private const BLURBS = [
        'TSHIRT-CLASSIC' => 'تصاميم مخصصة بطباعة واضحة ومظهر يومي أنيق.',
        'HOODIE-PREMIUM' => 'خيار مريح بطباعة ممتازة يناسب الاستخدام اليومي.',
        'CAP-CLASSIC' => 'قبعات بطابع بسيط مع إمكانية تخصيص التصميم.',
        'TOTE-CANVAS' => 'حقائب عملية بتصاميم مطبوعة تناسب الهدايا والاستخدام اليومي.',
        'SCARF-CUSTOM' => 'وشاحات بطباعة خاصة ولمسة ناعمة تناسب المناسبات.',
        'PHONE-CASE' => 'حماية أنيقة للموبايل مع تصاميم قابلة للتخصيص.',
        'MUG-CERAMIC' => 'أكواب مطبوعة بجودة عالية تناسب البيت والعمل.',
        'PAPER-PRINT' => 'طباعة ورق متنوعة للتغليف والعرض بجودة واضحة.',
        'NOTEBOOK-CUSTOM' => 'دفاتر بتصميم خاص تناسب الدراسة والعمل والهدايا.',
        'POSTER-PRINT' => 'بوسترات مطبوعة بجودة واضحة لعرض الأفكار والديكور.',
        'STICKER-CUSTOM' => 'ستيكرات مخصصة بأشكال متعددة ولمسات جذابة.',
        'WEDDING-CARDS' => 'كروت أفراح بتصاميم فخمة تناسب المناسبات الخاصة.',
    ];

    /** Product picker for the customer's own uploaded design (same catalog the designer picks from). */
    public function chooseProduct(): View
    {
        return view('customer.chooseProduct', ['designerCatalog' => CatalogProductData::forDesigner(true)]);
    }

    public function store(): View
    {
        $products = Product::query()
            ->with(['category', 'branchProductOfferings'])
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product) => $this->storeProduct($product))
            ->sortBy('order')
            ->values();

        return view('customer.store', ['products' => $products]);
    }

    public function tshirts(): View
    {
        return $this->productDesigns('TSHIRT-CLASSIC', 'customer.tshirts', 'front/assets/images/customer/tshirt.webp');
    }

    public function hoodies(): View
    {
        return $this->productDesigns('HOODIE-PREMIUM', 'customer.hoodies', 'front/assets/images/customer/hoodie.png');
    }

    public function mugs(): View
    {
        return $this->productDesigns('MUG-CERAMIC', 'customer.mugs', 'front/assets/images/customer/cup.webp');
    }

    /** Ready stickers and the customer's own uploads, laid out together on A4 sheets. */
    public function stickers(StickerSettingsService $settings): View
    {
        $view = $this->productDesigns('STICKER-CUSTOM', 'customer.stickers', 'front/assets/images/customer/icons8-sticker-48.png');

        return $view->with([
            'stickerCategories' => StickerCategory::query()
                ->where('is_active', true)
                ->with(['stickers' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (StickerCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'stickers' => $category->stickers->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'image_url' => $item->imageUrl(),
                    ])->values(),
                ])->values(),
            'stickerSettings' => $settings->get(),
            'stickerMaterials' => BranchProductOffering::query()->whereHas('product', fn ($q) => $q->where('code', 'STICKER-CUSTOM'))->where('is_active', true)->orderBy('base_price')->get()->map(fn ($offering) => $offering->sticker_materials ?: [])->filter()->first() ?: [],
            'stickerRoutes' => [
                'preview' => route('customer.stickers.preview'),
                'store' => route('customer.cart.store-sticker-bundle'),
                'login' => route('login'),
            ],
            'stickerUser' => ['authenticated' => auth()->check()],
        ]);
    }

    public function paperPrinting(): View
    {
        $product = Product::query()
            ->with([
                'branchProductOfferings.printProviderBranch.printProvider',
                'branchProductOfferings.branchOfferingVariants',
            ])
            ->where('code', 'PAPER-PRINT')
            ->first();

        return view('customer.paperPrinting', [
            'product' => $product,
            'paperPrinting' => $this->paperPrintingData($product),
        ]);
    }

    /**
     * What the paper printing page offers: only the sizes, paper types, print colours and bindings that at least one live
     * print shop sells. The prices are not here; the page asks the server for the price of the job (see CartController::quotePaper).
     *
     * @return array<string, mixed>
     */
    private function paperPrintingData(?Product $product): array
    {
        $empty = [
            'available' => false,
            'product' => null,
            'options' => ['availableValues' => ['size' => [], 'paper' => [], 'color' => []]],
            'bindings' => [],
        ];

        if (! $product || ! $product->is_active) {
            return $empty;
        }

        $pricing = app(PaperPricing::class);
        $sizeByVariant = $pricing->sizeByVariant($product->id);

        $offerings = $product->branchProductOfferings
            ->filter(fn ($offering) => $offering->is_active
                && (float) $offering->base_price > 0
                && $offering->printProviderBranch?->is_active
                && $offering->printProviderBranch->printProvider?->approval_status === 'approved'
                && $offering->printProviderBranch->printProvider->is_active);

        // A job needs a size, a paper type and a colour together: each combination some live shop can print.
        $combos = [];
        $bindings = [];

        foreach ($offerings as $offering) {
            $list = $pricing->priceList($offering);
            $shopSizes = $offering->branchOfferingVariants->where('is_available', true)
                ->map(fn ($variant) => $sizeByVariant[$variant->variant_id] ?? null)->filter()->unique()->all();

            foreach ($list['ink'] as $type => $byColor) {
                foreach ($shopSizes as $size) {
                    foreach (array_keys($byColor) as $color) {
                        $combos[$size.'|'.$type.'|'.$color] = ['size' => strtoupper($size), 'paper' => $type, 'color' => $color];
                    }
                }
            }

            foreach ($list['bindings'] as $binding => $price) {
                $bindings[$binding] = min($bindings[$binding] ?? $price, $price);
            }
        }

        $combos = array_values($combos);
        $available = $combos !== [];

        return [
            'available' => $available,
            'product' => ['id' => $product->id, 'code' => $product->code, 'name' => $product->name, 'is_active' => $product->is_active],
            'options' => [
                'availableValues' => [
                    'size' => array_values(array_unique(array_column($combos, 'size'))),
                    'paper' => array_values(array_unique(array_column($combos, 'paper'))),
                    'color' => array_values(array_unique(array_column($combos, 'color'))),
                ],
                // What can be printed together, so the page can hide a choice that does not go with the ones already made.
                'combinations' => $combos,
            ],
            // The lowest price among the shops that sell each binding, to show on its card ("from").
            'bindings' => collect($bindings)->map(fn ($price, $id) => ['id' => $id, 'from' => $price])->values()->all(),
        ];
    }

    private function productDesigns(string $productCode, string $view, string $fallbackImage): View
    {
        $product = Product::query()
            ->with(['attributes.attribute', 'attributes.values.attributeValue', 'branchProductOfferings.branchPrintAreas'])
            ->where('code', $productCode)
            ->where('is_active', true)
            ->first();

        $designs = collect();

        if ($product) {
            $publishedDesigns = Design::query()
                ->with(['designer', 'product'])
                ->where('product_id', $product->id)
                ->where('status', 'published')
                ->latest('published_at')
                ->latest()
                ->get();

            $favoriteIds = DesignFavorite::query()
                ->where('user_id', auth()->id())
                ->whereIn('design_id', $publishedDesigns->pluck('id'))
                ->pluck('design_id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $designs = $publishedDesigns
                ->map(fn (Design $design) => $this->publishedDesign(
                    $design,
                    $fallbackImage,
                    in_array((string) $design->id, $favoriteIds, true)
                ));
        }

        return view($view, [
            'product' => $product,
            'publishedDesigns' => $designs,
            'productOptions' => $product ? CatalogProductData::catalogOptions($product) : ['colors' => [], 'sizes' => []],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function storeProduct(Product $product): array
    {
        $meta = $this->productMeta($product);
        $price = $product->branchProductOfferings->where('is_active', true)->min('base_price');
        $isAvailable = $product->is_active && isset($meta['route']);

        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $meta['store_name'] ?? $product->name,
            'description' => $product->description,
            'category' => $meta['store_category'] ?? $product->category?->slug ?? 'catalog',
            'product_key' => $meta['product_key'] ?? str($product->code)->lower()->replace('_', '-')->toString(),
            'image' => asset($product->image ?: 'front/assets/images/customer/products/1.png'),
            'custom_image' => str_starts_with((string) $product->image, 'storage/'),
            'route' => $isAvailable ? $meta['route'] : null,
            'available' => $isAvailable,
            'status_label' => match (true) {
                ! $product->is_active => 'موقوف حالياً',
                ! $isAvailable => 'غير متاح حالياً',
                default => null,
            },
            'blurb' => $product->description ?: (self::BLURBS[strtoupper($product->code)] ?? 'تصميم مخصص بطباعة واضحة وجودة عالية.'),
            'price' => $price ? (float) $price : null,
            'order' => $meta['order'] ?? $product->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publishedDesign(Design $design, string $fallbackImage, bool $isFavorite = false): array
    {
        $options = $design->selected_options ?? [];
        $audience = CatalogProductData::AUDIENCES[$options['display_category'] ?? ''] ?? null;
        $allowedSizes = $audience
            ? collect($audience['sizes'])->filter(fn ($size) => in_array($size['id'], $options['allowed_size_ids'] ?? [], true))->values()->all()
            : [];

        return [
            'id' => (string) $design->id,
            'allowedColors' => array_values($options['allowed_color_ids'] ?? []),
            'allowedSizes' => $allowedSizes,
            'category' => $options['display_category'] ?? 'adults',
            'title' => $design->title,
            'description' => $design->description ?: $design->product?->name,
            'designer' => $design->designer?->name ?? 'PalPrints Designer',
            'price' => (float) ($design->selling_price ?: $design->base_price),
            'image' => $design->image ? asset($design->image) : asset($fallbackImage),
            // What the designer placed on each print area, so the preview can draw it on any colour; the uploaded pictures
            // are private files, so each one is given a link that only works for a published design.
            'layout' => $this->previewLayout($design),
            'assets' => $this->previewAssets($design),
            'is_favorite' => $isFavorite,
            'favorite_url' => route('customer.designs.favorite', $design),
        ];
    }

    /**
     * The objects the designer placed, per print area (positions and sizes are fractions of the print zone).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function previewLayout(Design $design): array
    {
        return collect($design->design_payload['layout']['areas'] ?? [])
            ->map(fn ($area) => array_values(array_filter((array) ($area['objects'] ?? []), 'is_array')))
            ->filter()
            ->all();
    }

    /** @return array<string, string> studio asset id => link to the designer's uploaded picture */
    private function previewAssets(Design $design): array
    {
        return collect($design->design_payload['files'] ?? [])
            ->filter(fn ($file) => ! empty($file['asset_id']) && str_starts_with((string) ($file['mime_type'] ?? ''), 'image/'))
            ->mapWithKeys(fn ($file) => [(string) $file['asset_id'] => route('customer.designs.asset', [$design, $file['asset_id']], false)])
            ->all();
    }

    /** A picture the designer uploaded into a design that is on sale, shown in the customer's preview of that design. */
    public function designAsset(Design $design, string $assetId)
    {
        abort_unless($design->status === 'published', 404);

        $file = collect($design->design_payload['files'] ?? [])->firstWhere('asset_id', $assetId);

        abort_unless($file && str_starts_with((string) ($file['mime_type'] ?? ''), 'image/') && Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->response($file['path'], null, [
            'Content-Type' => $file['mime_type'],
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox", // keeps an uploaded SVG from running scripts
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productMeta(Product $product): array
    {
        return match (strtoupper($product->code)) {
            'TSHIRT-CLASSIC' => [
                'store_name' => 'تيشيرت',
                'product_key' => 'shirt',
                'route' => route('customer.tshirts'),
                'order' => 1,
            ],
            'HOODIE-PREMIUM' => [
                'store_name' => 'هودي',
                'product_key' => 'hoodie',
                'route' => route('customer.hoodies'),
                'order' => 2,
            ],
            'PAPER-PRINT' => [
                'store_name' => 'طباعة ورق',
                'product_key' => 'paper',
                'route' => route('customer.paperPrinting'),
                'order' => 3,
            ],
            'STICKER-CUSTOM' => [
                'store_name' => 'ستيكرات',
                'product_key' => 'stickers',
                'route' => route('customer.stickers'),
                'order' => 4,
            ],
            'MUG-CERAMIC' => [
                'store_name' => 'أكواب',
                'store_category' => 'drinkware',
                'product_key' => 'cups',
                'route' => route('customer.mugs'),
                'order' => 5,
            ],
            'CAP-CLASSIC' => [
                'store_name' => 'قبعات',
                'product_key' => 'cap',
                'route' => null,
                'order' => 6,
            ],
            'TOTE-CANVAS' => [
                'store_name' => 'حقائب',
                'product_key' => 'bag',
                'route' => null,
                'order' => 7,
            ],
            'SCARF-CUSTOM' => [
                'store_name' => 'وشاحات',
                'product_key' => 'scarf',
                'route' => null,
                'order' => 8,
            ],
            'PHONE-CASE' => [
                'store_name' => 'كفرات موبايل',
                'product_key' => 'phone-case',
                'route' => null,
                'order' => 9,
            ],
            'NOTEBOOK-CUSTOM' => [
                'store_name' => 'دفاتر',
                'product_key' => 'notebooks',
                'route' => null,
                'order' => 10,
            ],
            'POSTER-PRINT' => [
                'store_name' => 'بوسترات',
                'product_key' => 'posters',
                'route' => null,
                'order' => 11,
            ],
            'WEDDING-CARDS' => [
                'store_name' => 'كروت أفراح',
                'store_category' => 'office',
                'product_key' => 'wedding-cards',
                'route' => null,
                'order' => 12,
            ],
            default => [],
        };
    }
}
