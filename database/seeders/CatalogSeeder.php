<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\PrintArea;
use App\Models\PrintingMethod;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Provider;
use App\Models\ProviderOffering;
use App\Models\ProviderOfferingVariant;
use App\Models\Variant;
use App\Models\VariantValue;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $provider = Provider::firstOrCreate(
            ['email' => 'printer@palprints.test'],
            [
                'name' => 'مطبعة PalPrints الرئيسية',
                'phone' => '0590000000',
                'license_number' => 'PP-0001',
                'status' => 'active',
            ]
        );

        $printingMethod = PrintingMethod::firstOrCreate(
            ['code' => 'dtg'],
            ['name' => 'طباعة رقمية مباشرة', 'is_active' => true]
        );

        $colorAttribute = Attribute::firstOrCreate(
            ['code' => 'color'],
            ['name' => 'اللون', 'data_type' => 'color', 'is_active' => true]
        );

        $sizeAttribute = Attribute::firstOrCreate(
            ['code' => 'size'],
            ['name' => 'المقاس', 'data_type' => 'string', 'is_active' => true]
        );

        $catalog = [
            [
                'category' => ['slug' => 'tshirts', 'name' => 'تي شيرت'],
                'product' => [
                    'code' => 'tshirt-classic',
                    'name' => 'تي شيرت كلاسيكي',
                    'description' => 'تي شيرت كلاسيكي عالي الجودة 100% قطن',
                ],
                'price' => 29,
                'colors' => [
                    ['code' => 'tshirt-white', 'name' => 'أبيض', 'hex' => '#ffffff', 'image' => 'front/designer/source/create/assets/images/tshirt.webp', 'default' => true],
                    ['code' => 'tshirt-black', 'name' => 'أسود', 'hex' => '#000000', 'image' => 'front/designer/source/create/assets/images/tshirt.webp'],
                    ['code' => 'tshirt-navy', 'name' => 'كحلي', 'hex' => '#173B87', 'image' => 'front/designer/source/create/assets/images/tshirt.webp'],
                    ['code' => 'tshirt-red', 'name' => 'أحمر', 'hex' => '#D52A3C', 'image' => 'front/designer/source/create/assets/images/tshirt.webp'],
                    ['code' => 'tshirt-green', 'name' => 'أخضر', 'hex' => '#2E9B42', 'image' => 'front/designer/source/create/assets/images/tshirt.webp'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                'printAreas' => [
                    ['code' => 'front', 'name' => 'الأمام', 'icon' => 'bi bi-person-standing', 'width_mm' => 280, 'height_mm' => 360],
                    ['code' => 'back', 'name' => 'الخلف', 'icon' => 'bi bi-person-standing', 'width_mm' => 280, 'height_mm' => 360],
                    ['code' => 'right-sleeve', 'name' => 'الكم الأيمن', 'icon' => 'bi bi-arrow-right', 'width_mm' => 100, 'height_mm' => 120],
                    ['code' => 'left-sleeve', 'name' => 'الكم الأيسر', 'icon' => 'bi bi-arrow-left', 'width_mm' => 100, 'height_mm' => 120],
                ],
                'thumbnail' => 'front/designer/source/create/assets/images/tshirt.webp',
            ],
            [
                'category' => ['slug' => 'hoodies', 'name' => 'هودي'],
                'product' => [
                    'code' => 'hoodie-simple',
                    'name' => 'هودي بسيط',
                    'description' => 'هودي مريح للاستخدام اليومي',
                ],
                'price' => 79,
                'colors' => [
                    ['code' => 'hoodie-white', 'name' => 'أبيض', 'hex' => '#ffffff', 'image' => 'front/designer/source/create/assets/images/hoodie.png', 'default' => true],
                    ['code' => 'hoodie-black', 'name' => 'أسود', 'hex' => '#000000', 'image' => 'front/designer/source/create/assets/images/hoodie-black.png'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
                'printAreas' => [
                    ['code' => 'front', 'name' => 'الأمام', 'icon' => 'bi bi-person-standing', 'width_mm' => 280, 'height_mm' => 360],
                    ['code' => 'back', 'name' => 'الخلف', 'icon' => 'bi bi-person-standing', 'width_mm' => 280, 'height_mm' => 360],
                ],
                'thumbnail' => 'front/designer/source/create/assets/images/hoodie.png',
            ],
            [
                'category' => ['slug' => 'mugs', 'name' => 'أكواب'],
                'product' => [
                    'code' => 'mug-ceramic',
                    'name' => 'كوب سيراميك',
                    'description' => 'كوب سيراميك عالي الجودة',
                ],
                'price' => 19,
                'colors' => [
                    ['code' => 'mug-white', 'name' => 'أبيض', 'hex' => '#ffffff', 'image' => 'front/designer/source/create/assets/images/cup.webp', 'default' => true],
                ],
                'sizes' => ['قياسي'],
                'printAreas' => [
                    ['code' => 'front', 'name' => 'الواجهة', 'icon' => 'bi bi-cup-hot', 'width_mm' => 200, 'height_mm' => 90],
                ],
                'thumbnail' => 'front/designer/source/create/assets/images/cup.webp',
            ],
            [
                'category' => ['slug' => 'bags', 'name' => 'حقائب'],
                'product' => [
                    'code' => 'bag-canvas',
                    'name' => 'حقيبة قماشية',
                    'description' => 'حقيبة قماشية عملية ومتينة',
                ],
                'price' => 39,
                'colors' => [
                    ['code' => 'bag-white', 'name' => 'أبيض', 'hex' => '#ffffff', 'image' => 'front/designer/source/create/assets/images/bag.png', 'default' => true],
                ],
                'sizes' => ['قياسي'],
                'printAreas' => [
                    ['code' => 'front', 'name' => 'الأمام', 'icon' => 'bi bi-bag', 'width_mm' => 280, 'height_mm' => 300],
                    ['code' => 'back', 'name' => 'الخلف', 'icon' => 'bi bi-bag', 'width_mm' => 280, 'height_mm' => 300],
                ],
                'thumbnail' => 'front/designer/source/create/assets/images/bag.png',
            ],
            [
                'category' => ['slug' => 'tshirts', 'name' => 'تي شيرت'],
                'product' => [
                    'code' => 'tshirt-heavyweight-faded',
                    'name' => 'تي شيرت ثقيل باهت',
                    'description' => 'تي شيرت ثقيل بقصة مريحة وألوان باهتة عصرية',
                ],
                'price' => 49,
                'colors' => [
                    ['code' => 'tshirt2-faded-black', 'name' => 'أسود باهت', 'hex' => '#4A4A48', 'image' => 'front/designer/source/create/assets/products/tshirt-2/tshirt-dyed-heavyweight-faded-black-removebg-preview.png', 'default' => true],
                    ['code' => 'tshirt2-faded-brown', 'name' => 'بني باهت', 'hex' => '#9B816A', 'image' => 'front/designer/source/create/assets/products/tshirt-2/tshirt-dyed-heavyweight-faded-brown-removebg-preview.png'],
                    ['code' => 'tshirt2-faded-cream', 'name' => 'كريمي باهت', 'hex' => '#F1EBDD', 'image' => 'front/designer/source/create/assets/products/tshirt-2/tshirt-dyed-heavyweight-faded-cream-removebg-preview.png'],
                    ['code' => 'tshirt2-faded-navy', 'name' => 'كحلي باهت', 'hex' => '#345775', 'image' => 'front/designer/source/create/assets/products/tshirt-2/tshirt-dyed-heavyweight-faded-navy-removebg-preview.png'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                'printAreas' => [
                    ['code' => 'front', 'name' => 'الأمام', 'icon' => 'bi bi-person-standing', 'width_mm' => 280, 'height_mm' => 360],
                    ['code' => 'back', 'name' => 'الخلف', 'icon' => 'bi bi-person-standing', 'width_mm' => 280, 'height_mm' => 360],
                    ['code' => 'right-sleeve', 'name' => 'الكم الأيمن', 'icon' => 'bi bi-arrow-right', 'width_mm' => 100, 'height_mm' => 120],
                    ['code' => 'left-sleeve', 'name' => 'الكم الأيسر', 'icon' => 'bi bi-arrow-left', 'width_mm' => 100, 'height_mm' => 120],
                ],
                'thumbnail' => 'front/designer/source/create/assets/products/tshirt-2/tshirt-dyed-heavyweight-faded-black-removebg-preview.png',
            ],
            [
                'category' => ['slug' => 'caps', 'name' => 'قبعات'],
                'product' => [
                    'code' => 'cap-classic',
                    'name' => 'قبعة كلاسيكية',
                    'description' => 'قبعة كلاسيكية قابلة للتعديل ومناسبة للطباعة الأمامية',
                ],
                'price' => 25,
                'colors' => [
                    ['code' => 'cap-black', 'name' => 'أسود', 'hex' => '#171717', 'image' => 'front/designer/source/create/assets/products/cap/cap-black-removebg-preview.png', 'default' => true],
                    ['code' => 'cap-navy', 'name' => 'كحلي', 'hex' => '#1D1E2B', 'image' => 'front/designer/source/create/assets/products/cap/cap-navy-removebg-preview.png'],
                    ['code' => 'cap-storm', 'name' => 'رمادي فاتح', 'hex' => '#D3D3D3', 'image' => 'front/designer/source/create/assets/products/cap/cap-storm-removebg-preview.png'],
                    ['code' => 'cap-walnut', 'name' => 'جوزي', 'hex' => '#786551', 'image' => 'front/designer/source/create/assets/products/cap/cap-wallnut-removebg-preview.png'],
                ],
                'sizes' => ['قياسي'],
                'printAreas' => [
                    ['code' => 'front', 'name' => 'الواجهة', 'icon' => 'bi bi-bullseye', 'width_mm' => 180, 'height_mm' => 80],
                ],
                'thumbnail' => 'front/designer/source/create/assets/products/cap/cap-black-removebg-preview.png',
            ],
        ];

        foreach ($catalog as $entry) {
            $category = Category::firstOrCreate(
                ['slug' => $entry['category']['slug']],
                ['name' => $entry['category']['name'], 'is_active' => true]
            );

            $product = Product::firstOrCreate(
                ['code' => $entry['product']['code']],
                [
                    'category_id' => $category->id,
                    'name' => $entry['product']['name'],
                    'description' => $entry['product']['description'],
                    'is_active' => true,
                ]
            );

            $offering = ProviderOffering::firstOrCreate(
                ['provider_id' => $provider->id, 'product_id' => $product->id],
                [
                    'base_price' => $entry['price'],
                    'currency' => 'USD',
                    'production_time_min' => 2,
                    'production_time_max' => 5,
                    'daily_capacity' => 50,
                    'is_active' => true,
                ]
            );

            $colorProductAttribute = ProductAttribute::firstOrCreate(
                ['product_id' => $product->id, 'attribute_id' => $colorAttribute->id],
                ['is_variant_axis' => true, 'is_required' => true, 'sort_order' => 1]
            );

            $sizeProductAttribute = ProductAttribute::firstOrCreate(
                ['product_id' => $product->id, 'attribute_id' => $sizeAttribute->id],
                ['is_variant_axis' => true, 'is_required' => true, 'sort_order' => 2]
            );

            $colorProductAttributeValues = [];

            foreach ($entry['colors'] as $color) {
                $attributeValue = AttributeValue::firstOrCreate(
                    ['attribute_id' => $colorAttribute->id, 'code' => $color['code']],
                    [
                        'value' => json_encode([
                            'name' => $color['name'],
                            'hex' => $color['hex'],
                            'image' => $color['image'],
                        ], JSON_UNESCAPED_UNICODE),
                        'sort_order' => 0,
                    ]
                );

                $productAttributeValue = ProductAttributeValue::firstOrCreate(
                    [
                        'product_attribute_id' => $colorProductAttribute->id,
                        'attribute_value_id' => $attributeValue->id,
                    ],
                    ['is_active' => true]
                );

                $colorProductAttributeValues[$color['code']] = $productAttributeValue;
            }

            $sizeProductAttributeValues = [];

            foreach ($entry['sizes'] as $index => $sizeName) {
                $attributeValue = AttributeValue::firstOrCreate(
                    ['attribute_id' => $sizeAttribute->id, 'code' => $product->code.'-'.$sizeName],
                    ['value' => json_encode(['name' => $sizeName], JSON_UNESCAPED_UNICODE), 'sort_order' => $index]
                );

                $productAttributeValue = ProductAttributeValue::firstOrCreate(
                    [
                        'product_attribute_id' => $sizeProductAttribute->id,
                        'attribute_value_id' => $attributeValue->id,
                    ],
                    ['is_active' => true]
                );

                $sizeProductAttributeValues[$sizeName] = $productAttributeValue;
            }

            foreach ($colorProductAttributeValues as $colorCode => $colorPav) {
                foreach ($sizeProductAttributeValues as $sizeName => $sizePav) {
                    $variant = Variant::firstOrCreate(
                        ['sku' => strtoupper($product->code.'-'.$colorCode.'-'.$sizeName)],
                        ['product_id' => $product->id, 'is_active' => true]
                    );

                    VariantValue::firstOrCreate([
                        'variant_id' => $variant->id,
                        'product_attribute_value_id' => $colorPav->id,
                    ]);

                    VariantValue::firstOrCreate([
                        'variant_id' => $variant->id,
                        'product_attribute_value_id' => $sizePav->id,
                    ]);

                    ProviderOfferingVariant::firstOrCreate([
                        'provider_offering_id' => $offering->id,
                        'variant_id' => $variant->id,
                    ], ['is_available' => true]);
                }
            }

            foreach ($entry['printAreas'] as $area) {
                PrintArea::updateOrCreate(
                    ['provider_offering_id' => $offering->id, 'code' => $area['code']],
                    [
                        'name' => $area['name'],
                        'max_width_mm' => $area['width_mm'],
                        'max_height_mm' => $area['height_mm'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
