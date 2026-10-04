<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\DesignFavorite;
use App\Models\Product;
use App\Support\CatalogProductData;
use Illuminate\Contracts\View\View;

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
        return view('customer.chooseProduct', ['designerCatalog' => CatalogProductData::forDesigner()]);
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

    public function stickers(): View
    {
        return $this->productDesigns('STICKER-CUSTOM', 'customer.stickers', 'front/assets/images/customer/icons8-sticker-48.png');
    }

    public function paperPrinting(): View
    {
        $product = Product::query()
            ->with([
                'branchProductOfferings.printProviderBranch',
                'branchProductOfferings.branchOfferingVariants.variant.values.productAttributeValue.attributeValue',
                'branchProductOfferings.branchOfferingVariants.variant.values.productAttributeValue.productAttribute.attribute',
                'branchProductOfferings.branchPricingRules.branchPrintCapability.printingMethod',
            ])
            ->where('code', 'PAPER-PRINT')
            ->first();

        return view('customer.paperPrinting', [
            'product' => $product,
            'paperPrinting' => $this->paperPrintingData($product),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paperPrintingData(?Product $product): array
    {
        if (! $product) {
            return [
                'available' => false,
                'product' => null,
                'options' => ['sizes' => [], 'availableValues' => ['size' => []]],
                'pricing' => ['basePrice' => 0, 'methodAddon' => 0, 'currency' => 'ILS'],
            ];
        }

        $offerings = $product->branchProductOfferings
            ->filter(fn ($offering) => $offering->is_active && $offering->printProviderBranch?->is_active)
            ->values();

        $branchVariants = $offerings
            ->flatMap(fn ($offering) => $offering->branchOfferingVariants)
            ->filter(fn ($branchVariant) => $branchVariant->is_available && $branchVariant->variant?->is_active)
            ->values();

        $sizes = $branchVariants
            ->map(function ($branchVariant) {
                $sizeValue = $branchVariant->variant->values
                    ->map(fn ($value) => $value->productAttributeValue)
                    ->first(fn ($value) => $value?->productAttribute?->attribute?->code === 'size')
                    ?->attributeValue;

                if (! $sizeValue) {
                    return null;
                }

                return [
                    'id' => strtoupper($sizeValue->value),
                    'code' => $sizeValue->code,
                    'name' => $sizeValue->value,
                    'variant_id' => $branchVariant->variant_id,
                ];
            })
            ->filter()
            ->unique('id')
            ->sortBy('id')
            ->values();

        $pricingRules = $offerings->flatMap(fn ($offering) => $offering->branchPricingRules)
            ->filter(fn ($rule) => $rule->is_active)
            ->values();

        $methodAddon = $pricingRules
            ->filter(fn ($rule) => $rule->pricing_type === 'print_method_addon')
            ->filter(fn ($rule) => $rule->branchPrintCapability?->printingMethod?->code === 'digital-paper')
            ->min('amount');

        return [
            'available' => $product->is_active && $offerings->isNotEmpty() && $sizes->isNotEmpty(),
            'product' => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'is_active' => $product->is_active,
            ],
            'options' => [
                'sizes' => $sizes->all(),
                'availableValues' => [
                    'size' => $sizes->pluck('id')->all(),
                ],
            ],
            'pricing' => [
                'basePrice' => (float) ($offerings->min('base_price') ?? 0),
                'methodAddon' => (float) ($methodAddon ?? 0),
                'currency' => $offerings->first()?->currency ?? 'ILS',
                'sheetBase' => [
                    'A5' => 0.12,
                    'A4' => 0.20,
                    'A3' => 0.42,
                ],
                'paperMultiplier' => [
                    'standard' => 1,
                    'thick' => 1.55,
                    'coated' => 2.1,
                ],
                'ink' => [
                    'bw' => 0.09,
                    'color' => 0.42,
                ],
            ],
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
            'is_favorite' => $isFavorite,
            'favorite_url' => route('customer.designs.favorite', $design),
        ];
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
