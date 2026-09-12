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

        Product::firstOrCreate(
            ['code' => 'TSHIRT-001'],
            [
                'category_id' => $apparel->id,
                'name' => 'Classic T-Shirt',
                'description' => 'Classic T-Shirt for custom printing',
                'is_active' => true,
            ]
        );
    }
}