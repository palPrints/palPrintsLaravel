<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProductCatalogController extends Controller
{
    private const PRINT_AREA_ICONS = [
        'front' => 'bi bi-person-standing',
        'back' => 'bi bi-person-standing',
        'right-sleeve' => 'bi bi-arrow-right',
        'left-sleeve' => 'bi bi-arrow-left',
    ];

    public function create(): View
    {
        return view('designer.designs.create', [
            'catalogData' => $this->catalogData(),
        ]);
    }

    public function editor(): View
    {
        return view('designer.designs.editor', [
            'catalogData' => $this->catalogData(),
        ]);
    }

    private function catalogData(): array
    {
        return [
            'categories' => $this->buildCategories(),
            'products' => $this->buildProducts(),
        ];
    }

    private function buildCategories(): array
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->slug,
                'name' => $category->name,
            ])
            ->all();

        return [['id' => 'all', 'name' => 'الكل'], ...$categories];
    }

    private function buildProducts(): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with([
                'category',
                'attributes.attribute',
                'attributes.values' => fn ($query) => $query->where('is_active', true),
                'attributes.values.attributeValue',
                'providerOfferings' => fn ($query) => $query->where('is_active', true)->orderBy('base_price'),
                'providerOfferings.printAreas' => fn ($query) => $query->where('is_active', true),
            ])
            ->get();

        return $products
            ->map(fn (Product $product) => $this->presentProduct($product))
            ->filter()
            ->values()
            ->all();
    }

    private function presentProduct(Product $product): ?array
    {
        $offering = $product->providerOfferings->first();

        if (! $offering) {
            return null;
        }

        $colors = $this->presentAttributeValues($product, 'color', fn (array $data) => [
            'value' => $data['hex'] ?? '#ffffff',
            'image' => isset($data['image']) ? asset($data['image']) : null,
        ]);

        $sizes = $this->presentAttributeValues($product, 'size');

        if ($colors->isEmpty() || $sizes->isEmpty()) {
            return null;
        }

        $printAreas = $offering->printAreas->map(fn ($area) => [
            'id' => $area->code,
            'name' => $area->name,
            'icon' => self::PRINT_AREA_ICONS[$area->code] ?? 'bi bi-bullseye',
            'dimensions' => $this->formatDimensions((float) $area->max_width_mm, (float) $area->max_height_mm),
        ])->all();

        return [
            'id' => $product->id,
            'code' => $product->code,
            'categoryId' => $product->category->slug,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $offering->base_price,
            'defaultColor' => $colors->first()['id'],
            'colors' => $colors->all(),
            'sizes' => $sizes->all(),
            'printAreas' => $printAreas,
            'thumbnail' => $colors->first()['image'],
        ];
    }

    private function formatDimensions(float $widthMm, float $heightMm): string
    {
        $widthCm = rtrim(rtrim(number_format($widthMm / 10, 1), '0'), '.');
        $heightCm = rtrim(rtrim(number_format($heightMm / 10, 1), '0'), '.');

        return "{$widthCm} × {$heightCm} سم";
    }

    private function presentAttributeValues(Product $product, string $attributeCode, ?callable $extra = null): Collection
    {
        $productAttribute = $product->attributes->first(
            fn ($productAttribute) => $productAttribute->attribute->code === $attributeCode
        );

        if (! $productAttribute) {
            return collect();
        }

        return $productAttribute->values
            ->map(function ($productAttributeValue) use ($extra) {
                $attributeValue = $productAttributeValue->attributeValue;
                $data = json_decode($attributeValue->value, true) ?: ['name' => $attributeValue->value];

                return array_merge([
                    'id' => $attributeValue->code,
                    'name' => $data['name'] ?? $attributeValue->value,
                ], $extra ? $extra($data) : []);
            })
            ->values();
    }
}
