<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $apparel = Category::where('slug', 'apparel')->firstOrFail();
        $accessories = Category::where('slug', 'accessories')->firstOrFail();

        Product::updateOrCreate(
            ['code' => 'TSHIRT-CLASSIC'],
            [
                'category_id' => $apparel->id,
                'name' => 'Classic T-Shirt',
                'description' => 'Cotton unisex t-shirt for custom printing.',
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['code' => 'MUG-CERAMIC'],
            [
                'category_id' => $accessories->id,
                'name' => 'Ceramic Mug',
                'description' => '330 ml ceramic mug for full-color printing.',
                'is_active' => true,
            ]
        );
    }
}