<?php

namespace App\Support;

use App\Models\AttributeValue;
use App\Models\BranchProductOffering;
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
                'branchProductOfferings.printProviderBranch',
                'branchProductOfferings.branchPrintAreas',
            ])
            ->where('is_active', true)
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
    private static function areaDefinitions(string $code): array
    {
        return match ($code) {
            'TSHIRT-CLASSIC' => [
                ['code' => 'front', 'name' => 'Front', 'width' => 300, 'height' => 400],
                ['code' => 'back', 'name' => 'Back', 'width' => 320, 'height' => 420],
            ],
            'HOODIE-PREMIUM' => [
                ['code' => 'front', 'name' => 'Front', 'width' => 280, 'height' => 340],
                ['code' => 'back', 'name' => 'Back', 'width' => 320, 'height' => 380],
            ],
            'MUG-CERAMIC' => [
                ['code' => 'wrap', 'name' => 'Full Wrap', 'width' => 200, 'height' => 80],
            ],
            'NOTEBOOK-CUSTOM' => [
                ['code' => 'cover', 'name' => 'Cover', 'width' => 148, 'height' => 210],
            ],
            'PHONE-CASE' => [
                ['code' => 'back', 'name' => 'Back', 'width' => 75, 'height' => 150],
            ],
            default => [
                ['code' => 'front', 'name' => 'Front', 'width' => 250, 'height' => 250],
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