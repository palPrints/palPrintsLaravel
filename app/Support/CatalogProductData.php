<?php

namespace App\Support;

use App\Models\AttributeValue;
use App\Models\BranchProductOffering;
use App\Models\Product;
use Illuminate\Support\Collection;

class CatalogProductData
{
    private const SWATCHES = [
        'white' => '#ffffff', 'black' => '#111111', 'navy' => '#173b87', 'natural' => '#e5d7bd',
        'gray' => '#737373', 'red' => '#b91c1c', 'green' => '#15803d', 'clear' => '#dbeafe',
    ];

    private const COLOR_NAMES = [
        'white' => 'أبيض', 'black' => 'أسود', 'navy' => 'كحلي', 'natural' => 'طبيعي',
        'gray' => 'رمادي', 'red' => 'أحمر', 'green' => 'أخضر', 'clear' => 'شفاف',
    ];

    /** Printing-service products: customers upload their own files, so designers cannot design them. */
    public const NOT_DESIGNABLE = ['PAPER-PRINT', 'STICKER-CUSTOM'];

    /** Clothing is sold by audience; the designer picks one when starting a design. Other products have none. */
    public const APPAREL_CODES = ['TSHIRT-CLASSIC', 'HOODIE-PREMIUM'];

    /**
     * Audience => label and the sizes it offers. The kids sizes are not in the product attributes yet,
     * so the sizes live here until the database holds them per audience.
     */
    public const AUDIENCES = [
        'adults' => [
            'label' => 'رجال / نساء',
            'hint' => 'مقاسات S إلى XXL',
            'sizes' => [['id' => 'S', 'name' => 'S'], ['id' => 'M', 'name' => 'M'], ['id' => 'L', 'name' => 'L'], ['id' => 'XL', 'name' => 'XL'], ['id' => 'XXL', 'name' => 'XXL']],
        ],
        'oversized' => [
            'label' => 'أوفر سايز',
            'hint' => 'قصّة واسعة، مقاسات M إلى XXL',
            'sizes' => [['id' => 'M', 'name' => 'M'], ['id' => 'L', 'name' => 'L'], ['id' => 'XL', 'name' => 'XL'], ['id' => 'XXL', 'name' => 'XXL']],
        ],
        'kids' => [
            'label' => 'أطفال',
            'hint' => 'من 4 إلى 14 سنة',
            'sizes' => [['id' => '4', 'name' => '4 سنوات'], ['id' => '6', 'name' => '6 سنوات'], ['id' => '8', 'name' => '8 سنوات'], ['id' => '10', 'name' => '10 سنوات'], ['id' => '12', 'name' => '12 سنة'], ['id' => '14', 'name' => '14 سنة']],
        ],
    ];

    public static function isApparel(string $code): bool
    {
        return in_array(strtoupper($code), self::APPAREL_CODES, true);
    }

    /**
     * @return array{categories: array<int, array<string, string>>, products: array<int, array<string, mixed>>}
     */
    public static function forDesigner(): array
    {
        $products = Product::query()
            ->with([
                'category',
                'attributes.attribute',
                'attributes.values.attributeValue',
                'branchProductOfferings.branchPrintAreas',
            ])
            ->where('is_active', true)
            ->whereNotIn('code', self::NOT_DESIGNABLE)
            ->orderBy('id')
            ->get()
            ->sortBy(fn (Product $product) => self::productOrder($product->code))
            ->values();

        $categories = collect([['id' => 'all', 'name' => 'الكل']])
            ->merge($products->pluck('category')->filter()->unique('id')->map(fn ($category) => [
                'id' => $category->slug,
                'name' => $category->name,
            ]))
            ->values()
            ->all();

        return [
            'categories' => $categories,
            'products' => $products->map(fn (Product $product) => self::product($product))->values()->all(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function keyedForDesigner(): array
    {
        return collect(self::forDesigner()['products'])
            ->keyBy('id')
            ->map(fn (array $product) => self::editorProduct($product))
            ->all();
    }

    /**
     * Color/size choices for the customer catalog pages, straight from the
     * product's attribute values. The catalog holds two families of values
     * for the same thing ("tshirt-white" vs "white", JSON-encoded names vs
     * plain ones), so codes are normalized and duplicates dropped — the
     * first value seen wins, which keeps the Arabic names.
     *
     * @return array{colors: array<int, array<string, string>>, sizes: array<int, array<string, string>>}
     */
    public static function catalogOptions(Product $product): array
    {
        $attributes = self::attributeValues($product);

        return [
            'colors' => self::uniqueOptions($attributes['color'] ?? collect(), true),
            'sizes' => self::uniqueOptions($attributes['size'] ?? collect()),
            // Print areas the active print shops offer for this product (code + Arabic name).
            'printAreas' => $product->branchProductOfferings
                ->filter(fn (BranchProductOffering $offering) => $offering->is_active)
                ->flatMap(fn (BranchProductOffering $offering) => $offering->branchPrintAreas)
                ->where('is_active', true)
                ->groupBy('code')
                // Sizes differ per print shop, so use the smallest each shop supports: a design that fits it prints everywhere.
                ->map(fn ($areas, $code) => [
                    'id' => $code,
                    'name' => $areas->first()->name,
                    'widthMm' => (int) $areas->min('max_width_mm'),
                    'heightMm' => (int) $areas->min('max_height_mm'),
                ])
                ->values()
                ->all(),
        ];
    }

    public static function normalizeCode(string $code): string
    {
        $code = mb_strtolower(trim($code));
        $code = preg_replace('/^(tshirt-classic|hoodie-premium|mug-ceramic|tshirt|hoodie|mug)-/u', '', $code);

        return in_array($code, ['قياسي', 'standard', 'one size', 'one-size'], true) ? 'one-size' : $code;
    }

    /**
     * @return array{name: string, hex: ?string}
     */
    public static function decodeValue(AttributeValue $value): array
    {
        $decoded = json_decode((string) $value->value, true);

        return is_array($decoded)
            ? ['name' => (string) ($decoded['name'] ?? $value->code), 'hex' => $decoded['hex'] ?? null]
            : ['name' => (string) $value->value, 'hex' => null];
    }

    /**
     * @param  Collection<int, AttributeValue>  $values
     * @return array<int, array<string, string>>
     */
    private static function uniqueOptions(Collection $values, bool $isColor = false): array
    {
        return $values
            ->map(function (AttributeValue $value) use ($isColor) {
                $decoded = self::decodeValue($value);
                $id = self::normalizeCode($value->code);
                // Plain (non-JSON) color values are English ("White"); show the Arabic name.
                $arabic = $decoded['hex'] === null && $isColor ? (self::COLOR_NAMES[$id] ?? null) : null;

                return ['id' => $id, 'name' => $arabic ?? $decoded['name'], 'value' => $decoded['hex'] ?? self::swatch($id)];
            })
            ->unique('id')
            ->values()
            ->all();
    }

    public static function swatch(string $code): string
    {
        return self::SWATCHES[$code] ?? '#888888';
    }

    /**
     * @return array<string, mixed>
     */
    private static function product(Product $product): array
    {
        $meta = self::meta($product->code);
        $thumbnail = self::productImage($product, $meta);
        $attributes = self::attributeValues($product);
        $colors = self::colors($attributes['color'] ?? collect(), $thumbnail);
        $sizes = self::sizes($attributes['size'] ?? collect());
        $areas = self::printAreas($product, $meta, $thumbnail);
        $price = $product->branchProductOfferings->where('is_active', true)->min('base_price') ?? 0;

        return [
            'id' => (string) $product->id,
            'databaseId' => $product->id,
            'code' => $product->code,
            'categoryId' => $product->category?->slug ?? 'catalog',
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $price,
            'defaultColor' => $colors[0]['id'] ?? 'standard',
            'colors' => $colors,
            'sizes' => $sizes,
            'printAreas' => $areas,
            'thumbnail' => $thumbnail,
            'order' => self::productOrder($product->code),
        ];
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private static function editorProduct(array $product): array
    {
        $colorNames = collect($product['colors'])->mapWithKeys(fn (array $color) => [$color['id'] => $color['name']])->all();

        return [
            'name' => $product['name'],
            'price' => $product['price'],
            'colorName' => $colorNames,
            'image' => $product['thumbnail'],
            'colors' => $product['colors'],
            'sizes' => collect($product['sizes'])->pluck('id')->all(),
            'areas' => collect($product['printAreas'])->map(fn (array $area) => [
                'id' => $area['id'],
                'name' => $area['name'],
                'image' => $area['image'] ?? $product['thumbnail'],
                'dimensions' => $area['dimensions'] ?? '28 x 36 مم',
            ])->all(),
        ];
    }

    /**
     * @return array<string, Collection<int, AttributeValue>>
     */
    private static function attributeValues(Product $product): array
    {
        $result = [];

        foreach ($product->attributes as $productAttribute) {
            $code = $productAttribute->attribute?->code;
            if (! $code) {
                continue;
            }

            $result[$code] = $productAttribute->values
                ->where('is_active', true)
                ->pluck('attributeValue')
                ->filter()
                ->values();
        }

        return $result;
    }

    /**
     * @param  Collection<int, AttributeValue>  $values
     * @return array<int, array<string, string>>
     */
    private static function colors(Collection $values, string $thumbnail): array
    {
        return $values->map(fn (AttributeValue $value) => [
            'id' => $value->code,
            'name' => $value->value,
            'value' => self::swatch($value->code),
            'image' => $thumbnail,
        ])->values()->all() ?: [[
            'id' => 'standard',
            'name' => 'قياسي',
            'value' => '#ffffff',
            'image' => $thumbnail,
        ]];
    }

    /**
     * @param  Collection<int, AttributeValue>  $values
     * @return array<int, array<string, string>>
     */
    private static function sizes(Collection $values): array
    {
        return $values->map(fn (AttributeValue $value) => [
            'id' => $value->code,
            'name' => $value->value,
        ])->values()->all() ?: [['id' => 'standard', 'name' => 'قياسي']];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<int, array<string, string>>
     */
    private static function printAreas(Product $product, array $meta, string $thumbnail): array
    {
        $activeOfferings = $product->branchProductOfferings->filter(
            fn (BranchProductOffering $offering) => $offering->is_active
        );

        if ($activeOfferings->isEmpty()) {
            return [];
        }

        $areas = $activeOfferings
            ->flatMap(fn (BranchProductOffering $offering) => $offering->branchPrintAreas)
            ->where('is_active', true)
            ->unique('code')
            ->values();

        if ($areas->isNotEmpty()) {
            return $areas->map(fn ($area) => [
                'id' => $area->code,
                'name' => $area->name,
                'icon' => $meta['area_icons'][$area->code] ?? 'bi bi-bounding-box',
                'image' => $meta['area_images'][$area->code] ?? $thumbnail,
                'dimensions' => $area->max_width_mm.' x '.$area->max_height_mm.' مم',
            ])->values()->all();
        }

        return collect(self::areaDefinitions($product->code))->map(fn (array $area) => [
            'id' => $area['code'],
            'name' => $area['name'],
            'icon' => $meta['area_icons'][$area['code']] ?? 'bi bi-bounding-box',
            'image' => $meta['area_images'][$area['code']] ?? $thumbnail,
            'dimensions' => $area['width'].' x '.$area['height'].' مم',
        ])->values()->all();
    }

    /**
     * @return array<int, array{code: string, name: string, width: int, height: int}>
     */
    public static function areaDefinitions(string $code): array
    {
        // Sizes (mm) match the print zones the design studio draws (A4 on clothes, 20 x 9 cm on the mug).
        return match (strtoupper($code)) {
            'TSHIRT-CLASSIC' => [
                ['code' => 'front', 'name' => 'أمامي', 'width' => 210, 'height' => 297],
                ['code' => 'back', 'name' => 'خلفي', 'width' => 210, 'height' => 297],
                ['code' => 'right-sleeve', 'name' => 'الكم الأيمن', 'width' => 100, 'height' => 120],
                ['code' => 'left-sleeve', 'name' => 'الكم الأيسر', 'width' => 100, 'height' => 120],
            ],
            'HOODIE-PREMIUM' => [
                ['code' => 'front', 'name' => 'أمامي', 'width' => 210, 'height' => 297],
                ['code' => 'back', 'name' => 'خلفي', 'width' => 210, 'height' => 297],
            ],
            'MUG-CERAMIC' => [
                ['code' => 'wrap', 'name' => 'طباعة محيطية كاملة', 'width' => 200, 'height' => 90],
            ],
            'NOTEBOOK-CUSTOM' => [
                ['code' => 'cover', 'name' => 'غلاف', 'width' => 148, 'height' => 210],
            ],
            'PHONE-CASE' => [
                ['code' => 'back', 'name' => 'خلفي', 'width' => 75, 'height' => 150],
            ],
            default => [
                ['code' => 'front', 'name' => 'أمامي', 'width' => 250, 'height' => 250],
            ],
        };
    }

    private static function productImage(Product $product, array $meta): string
    {
        if ($product->image) {
            return asset($product->image);
        }

        return asset('front/designer/source/create/'.$meta['thumbnail']);
    }

    /** Arabic category label for the admin pages; falls back to the name stored in the database. */
    public static function categoryName(string $slug, string $fallback): string
    {
        return match ($slug) {
            'apparel' => 'ملابس',
            'accessories' => 'اكسسوارات',
            'drinkware' => 'أكواب',
            'office' => 'مطبوعات',
            default => $fallback,
        };
    }

    private static function productOrder(string $code): int
    {
        return match ($code) {
            'TSHIRT-CLASSIC' => 1,
            'HOODIE-PREMIUM' => 2,
            'PAPER-PRINT' => 3,
            'STICKER-CUSTOM' => 4,
            'MUG-CERAMIC' => 5,
            'CAP-CLASSIC' => 6,
            'TOTE-CANVAS' => 7,
            'SCARF-CUSTOM' => 8,
            'PHONE-CASE' => 9,
            'NOTEBOOK-CUSTOM' => 10,
            'POSTER-PRINT' => 11,
            'WEDDING-CARDS' => 12,
            default => 100,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function meta(string $code): array
    {
        return match ($code) {
            'HOODIE-PREMIUM' => [
                'thumbnail' => 'assets/images/hoodie.png',
                'area_images' => [
                    'front' => 'assets/images/printing-areas/hoodie/hoodie-front.png',
                    'back' => 'assets/images/printing-areas/hoodie/hoodie-back.png',
                ],
            ],
            'MUG-CERAMIC' => [
                'thumbnail' => 'assets/images/cup.webp',
                'area_images' => ['wrap' => 'assets/images/cup.webp'],
            ],
            'TOTE-CANVAS' => ['thumbnail' => 'assets/images/bag.png'],
            'CAP-CLASSIC' => ['thumbnail' => 'assets/products/cap/cap-black-removebg-preview.png'],
            'SCARF-CUSTOM' => ['thumbnail' => 'assets/images/tshirt.webp'],
            'PHONE-CASE' => ['thumbnail' => 'assets/images/cup.webp'],
            'PAPER-PRINT' => ['thumbnail' => 'assets/images/tshirt.webp'],
            'NOTEBOOK-CUSTOM' => ['thumbnail' => 'assets/images/bag.png'],
            'POSTER-PRINT' => ['thumbnail' => 'assets/images/tshirt.webp'],
            'STICKER-CUSTOM' => ['thumbnail' => 'assets/images/cup.webp'],
            'WEDDING-CARDS' => ['thumbnail' => 'assets/images/tshirt.webp'],
            default => [
                'thumbnail' => 'assets/images/tshirt.webp',
                'area_images' => [
                    'front' => 'assets/images/printing-areas/tshirt/tshirt-front-removebg-preview.png',
                    'back' => 'assets/images/printing-areas/tshirt/tshirt-back-removebg-preview.png',
                ],
            ],
        };
    }
}
