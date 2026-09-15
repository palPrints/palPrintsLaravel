<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class CatalogController extends Controller
{
    public function store(): View
    {
        $products = Product::query()
            ->with(['category', 'providerOfferings'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product) => $this->storeProduct($product));

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

    private function productDesigns(string $productCode, string $view, string $fallbackImage): View
    {
        $product = Product::query()
            ->where('code', $productCode)
            ->where('is_active', true)
            ->first();

        $designs = collect();

        if ($product) {
            $designs = Design::query()
                ->with(['designer', 'product'])
                ->where('product_id', $product->id)
                ->where('status', 'published')
                ->latest('published_at')
                ->latest()
                ->get()
                ->map(fn (Design $design) => $this->publishedDesign($design, $fallbackImage));
        }

        return view($view, [
            'product' => $product,
            'publishedDesigns' => $designs,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function storeProduct(Product $product): array
    {
        $meta = $this->productMeta($product);
        $price = $product->providerOfferings->where('is_active', true)->min('base_price');

        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $meta['store_name'] ?? $product->name,
            'description' => $product->description,
            'category' => $meta['store_category'] ?? $product->category?->slug ?? 'catalog',
            'product_key' => $meta['product_key'] ?? str($product->code)->lower()->replace('_', '-')->toString(),
            'image' => asset($meta['store_image'] ?? 'front/assets/images/customer/products/1.png'),
            'route' => $meta['route'] ?? null,
            'price' => $price ? (float) $price : null,
            'order' => $meta['order'] ?? $product->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publishedDesign(Design $design, string $fallbackImage): array
    {
        $options = $design->selected_options ?? [];

        return [
            'id' => (string) $design->id,
            'category' => $options['display_category'] ?? 'adults',
            'title' => $design->title,
            'description' => $design->description ?: $design->product?->name,
            'designer' => $design->designer?->name ?? 'PalPrints Designer',
            'price' => (float) ($design->selling_price ?: $design->base_price),
            'image' => $design->image ? asset($design->image) : asset($fallbackImage),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productMeta(Product $product): array
    {
        return match ($product->code) {
            'TSHIRT-CLASSIC' => [
                'store_name' => 'تيشيرت',
                'store_category' => 'clothing',
                'product_key' => 'shirt',
                'store_image' => 'front/assets/images/customer/products/1.png',
                'route' => route('customer.tshirts'),
                'order' => 1,
            ],
            'HOODIE-PREMIUM' => [
                'store_name' => 'هودي',
                'store_category' => 'clothing',
                'product_key' => 'hoodie',
                'store_image' => 'front/assets/images/customer/products/2.png',
                'route' => route('customer.hoodies'),
                'order' => 2,
            ],
            'MUG-CERAMIC' => [
                'store_name' => 'أكواب',
                'store_category' => 'drinkware',
                'product_key' => 'cups',
                'store_image' => 'front/assets/images/customer/products/7.png',
                'route' => route('customer.mugs'),
                'order' => 5,
            ],
            'TOTE-CANVAS' => [
                'store_name' => 'حقائب',
                'store_category' => 'accessories',
                'product_key' => 'bag',
                'store_image' => 'front/assets/images/customer/products/4.png',
                'route' => null,
                'order' => 7,
            ],
            default => [],
        };
    }
}
