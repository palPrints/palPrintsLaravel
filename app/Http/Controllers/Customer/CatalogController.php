<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class CatalogController extends Controller
{
    public function store(): View
    {
        $products = Product::query()
            ->with(['category', 'providerOfferings'])
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
        $isAvailable = $product->is_active && isset($meta['route']);

        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $meta['store_name'] ?? $product->name,
            'description' => $product->description,
            'category' => $meta['store_category'] ?? $product->category?->slug ?? 'catalog',
            'product_key' => $meta['product_key'] ?? str($product->code)->lower()->replace('_', '-')->toString(),
            'image' => asset($product->image ?: 'front/assets/images/customer/products/1.png'),
            'route' => $isAvailable ? $meta['route'] : null,
            'available' => $isAvailable,
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
                'route' => route('customer.tshirts'),
                'order' => 1,
            ],
            'HOODIE-PREMIUM' => [
                'store_name' => 'هودي',
                'store_category' => 'clothing',
                'product_key' => 'hoodie',
                'route' => route('customer.hoodies'),
                'order' => 2,
            ],
            'PAPER-PRINT' => [
                'store_name' => 'طباعة ورق',
                'store_category' => 'office',
                'product_key' => 'paper',
                'route' => null,
                'order' => 3,
            ],
            'STICKER-CUSTOM' => [
                'store_name' => 'ستيكرات',
                'store_category' => 'office',
                'product_key' => 'stickers',
                'route' => route('customer.stickers'),
                'order' => 4,
            ],
            'MUG-CERAMIC' => [
                'store_name' => 'اكواب',
                'store_category' => 'drinkware',
                'product_key' => 'cups',
                'route' => route('customer.mugs'),
                'order' => 5,
            ],
            'CAP-CLASSIC' => [
                'store_name' => 'قبعات',
                'store_category' => 'clothing',
                'product_key' => 'cap',
                'route' => null,
                'order' => 6,
            ],
            'TOTE-CANVAS' => [
                'store_name' => 'حقائب',
                'store_category' => 'accessories',
                'product_key' => 'bag',
                'route' => null,
                'order' => 7,
            ],
            'SCARF-CUSTOM' => [
                'store_name' => 'وشاحات',
                'store_category' => 'accessories',
                'product_key' => 'scarf',
                'route' => null,
                'order' => 8,
            ],
            'PHONE-CASE' => [
                'store_name' => 'كفرات موبايل',
                'store_category' => 'accessories',
                'product_key' => 'phone-case',
                'route' => null,
                'order' => 9,
            ],
            'NOTEBOOK-CUSTOM' => [
                'store_name' => 'دفاتر',
                'store_category' => 'office',
                'product_key' => 'notebooks',
                'route' => null,
                'order' => 10,
            ],
            'POSTER-PRINT' => [
                'store_name' => 'بوسترات',
                'store_category' => 'office',
                'product_key' => 'posters',
                'route' => null,
                'order' => 11,
            ],
            'WEDDING-CARDS' => [
                'store_name' => 'كروت افراح',
                'store_category' => 'office',
                'product_key' => 'wedding-cards',
                'route' => null,
                'order' => 12,
            ],
            default => [],
        };
    }
}
