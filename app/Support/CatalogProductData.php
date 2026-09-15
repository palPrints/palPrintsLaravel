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
            ->get();

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
     * @return array<string, mixed>
     */
    private static function product(Product $product): array
    {
        $meta = self::meta($product->code);
        $attributes = self::attributeValues($product);
        $colors = self::colors($attributes['color'] ?? collect(), $meta);
        $sizes = self::sizes($attributes['size'] ?? collect());
        $areas = self::printAreas($product, $meta);
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
            'thumbnail' => $colors[0]['image'] ?? $meta['thumbnail'],
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
     * @param  array<string, mixed>  $meta
     * @return array<int, array<string, string>>
     */
    private static function colors(Collection $values, array $meta): array
    {
        $swatches = [
            'white' => '#ffffff',
            'black' => '#111111',
            'navy' => '#173b87',
            'natural' => '#e5d7bd',
        ];

        $image = $meta['thumbnail'];

        return $values->map(fn (AttributeValue $value) => [
            'id' => $value->code,
            'name' => $value->value,
            'value' => $swatches[$value->code] ?? '#888888',
            'image' => $meta['color_images'][$value->code] ?? $image,
        ])->values()->all() ?: [[
            'id' => 'standard',
            'name' => 'قياسي',
            'value' => '#ffffff',
            'image' => $image,
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
    private static function printAreas(Product $product, array $meta): array
    {
        $areas = $product->providerOfferings
            ->flatMap->printAreas
            ->unique('code')
            ->values();

        return $areas->map(fn (PrintArea $area) => [
            'id' => $area->code,
            'name' => $area->name,
            'icon' => $meta['area_icons'][$area->code] ?? 'bi bi-bounding-box',
            'image' => $meta['area_images'][$area->code] ?? $meta['thumbnail'],
            'dimensions' => (int) $area->max_width_mm.' x '.(int) $area->max_height_mm.' مم',
        ])->all();
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
                'color_images' => ['black' => 'assets/images/hoodie-black.png'],
                'area_images' => ['front' => 'assets/images/printing-areas/hoodie/hoodie-front.png', 'back' => 'assets/images/printing-areas/hoodie/hoodie-back.png'],
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
