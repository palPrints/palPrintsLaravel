<?php

namespace App\Support;

use App\Models\AttributeValue;
use App\Models\PrintArea;
use App\Models\Product;
use Illuminate\Support\Collection;

class CatalogProductData
{
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
                'providerOfferings.printAreas',
            ])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->sortBy(fn (Product $product) => self::productOrder($product->code))
            ->values();

        $categories = collect([['id' => 'all', 'name' => 'الكل']])
            ->merge($products->pluck('category')->filter()->unique('id')->map(fn ($category) => [
                'id' => $category->slug,
                'name' => self::categoryName($category->slug, $category->name),
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
        $price = $product->providerOfferings->where('is_active', true)->min('base_price') ?? 0;

        return [
            'id' => (string) $product->id,
            'databaseId' => $product->id,
            'categoryId' => $product->category?->slug ?? 'catalog',
            'name' => $meta['designer_name'] ?? $product->name,
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
                'dimensions' => $area['dimensions'] ?? '28 x 36 سم',
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
        $swatches = [
            'white' => '#ffffff',
            'black' => '#111111',
            'navy' => '#173b87',
            'natural' => '#e5d7bd',
            'gray' => '#737373',
            'red' => '#b91c1c',
            'green' => '#15803d',
            'clear' => '#dbeafe',
        ];

        return $values->map(fn (AttributeValue $value) => [
            'id' => $value->code,
            'name' => $value->value,
            'value' => $swatches[$value->code] ?? '#888888',
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
        $areas = $product->providerOfferings
            ->flatMap->printAreas
            ->unique('code')
            ->values();

        return $areas->map(fn (PrintArea $area) => [
            'id' => $area->code,
            'name' => $area->name,
            'icon' => $meta['area_icons'][$area->code] ?? 'bi bi-bounding-box',
            'image' => $meta['area_images'][$area->code] ?? $thumbnail,
            'dimensions' => (int) $area->max_width_mm.' x '.(int) $area->max_height_mm.' مم',
        ])->all();
    }

    private static function productImage(Product $product, array $meta): string
    {
        if ($product->image) {
            return asset($product->image);
        }

        return asset('front/designer/source/create/'.$meta['thumbnail']);
    }

    private static function categoryName(string $slug, string $fallback): string
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
                'designer_name' => 'هودي بسيط',
                'thumbnail' => 'assets/images/hoodie.png',
                'area_images' => [
                    'front' => 'assets/images/printing-areas/hoodie/hoodie-front.png',
                    'back' => 'assets/images/printing-areas/hoodie/hoodie-back.png',
                ],
            ],
            'MUG-CERAMIC' => [
                'designer_name' => 'كوب سيراميك',
                'thumbnail' => 'assets/images/cup.webp',
                'area_images' => ['wrap' => 'assets/images/cup.webp'],
            ],
            'TOTE-CANVAS' => [
                'designer_name' => 'حقيبة قماشية',
                'thumbnail' => 'assets/images/bag.png',
            ],
            'CAP-CLASSIC' => [
                'designer_name' => 'قبعة كلاسيكية',
                'thumbnail' => 'assets/products/cap/cap-black-removebg-preview.png',
            ],
            'SCARF-CUSTOM' => [
                'designer_name' => 'وشاح مخصص',
                'thumbnail' => 'assets/images/tshirt.webp',
            ],
            'PHONE-CASE' => [
                'designer_name' => 'كفر موبايل',
                'thumbnail' => 'assets/images/cup.webp',
            ],
            'PAPER-PRINT' => [
                'designer_name' => 'طباعة ورق',
                'thumbnail' => 'assets/images/tshirt.webp',
            ],
            'NOTEBOOK-CUSTOM' => [
                'designer_name' => 'دفتر مخصص',
                'thumbnail' => 'assets/images/bag.png',
            ],
            'POSTER-PRINT' => [
                'designer_name' => 'بوستر',
                'thumbnail' => 'assets/images/tshirt.webp',
            ],
            'STICKER-CUSTOM' => [
                'designer_name' => 'ستيكر',
                'thumbnail' => 'assets/images/cup.webp',
            ],
            'WEDDING-CARDS' => [
                'designer_name' => 'كرت افراح',
                'thumbnail' => 'assets/images/tshirt.webp',
            ],
            default => [
                'designer_name' => 'تي شيرت كلاسيكي',
                'thumbnail' => 'assets/images/tshirt.webp',
                'area_images' => [
                    'front' => 'assets/images/printing-areas/tshirt/tshirt-front-removebg-preview.png',
                    'back' => 'assets/images/printing-areas/tshirt/tshirt-back-removebg-preview.png',
                ],
            ],
        };
    }
}
