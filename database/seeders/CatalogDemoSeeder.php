<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\PricingRule;
use App\Models\PrintArea;
use App\Models\PrintCapability;
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
use Illuminate\Support\Str;

class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $attributes = $this->seedAttributes();
        $products = $this->seedProducts($categories);
        $variants = $this->seedProductAttributesAndVariants($products, $attributes);
        $methods = $this->seedPrintingMethods();
        $providers = $this->seedProviders();

        $this->seedProviderCatalog($providers, $products, $variants, $methods);
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $data = [
            'apparel' => 'Apparel',
            'accessories' => 'Accessories',
            'home-office' => 'Home & Office',
        ];

        $categories = [];

        foreach ($data as $slug => $name) {
            $categories[$slug] = Category::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'parent_id' => null, 'is_active' => true],
            );
        }

        return $categories;
    }

    /**
     * @return array<string, array<string, AttributeValue>>
     */
    private function seedAttributes(): array
    {
        return [
            'color' => $this->attribute('Color', 'color', [
                'white' => 'White',
                'black' => 'Black',
                'navy' => 'Navy',
                'natural' => 'Natural',
            ]),
            'size' => $this->attribute('Size', 'size', [
                's' => 'S',
                'm' => 'M',
                'l' => 'L',
                'xl' => 'XL',
            ]),
            'material' => $this->attribute('Material', 'material', [
                'cotton' => 'Cotton',
                'fleece' => 'Fleece',
                'ceramic' => 'Ceramic',
                'canvas' => 'Canvas',
            ]),
            'print_side' => $this->attribute('Print Side', 'print_side', [
                'front' => 'Front',
                'back' => 'Back',
                'wrap' => 'Full Wrap',
            ]),
        ];
    }

    /**
     * @param  array<string, Category>  $categories
     * @return array<string, Product>
     */
    private function seedProducts(array $categories): array
    {
        $data = [
            'TSHIRT-CLASSIC' => [
                'category' => 'apparel',
                'name' => 'Classic T-Shirt',
                'description' => 'Cotton unisex t-shirt for everyday custom printing.',
            ],
            'HOODIE-PREMIUM' => [
                'category' => 'apparel',
                'name' => 'Premium Hoodie',
                'description' => 'Warm fleece hoodie with front and back print support.',
            ],
            'MUG-CERAMIC' => [
                'category' => 'accessories',
                'name' => 'Ceramic Mug',
                'description' => '330 ml ceramic mug for full-color sublimation.',
            ],
            'TOTE-CANVAS' => [
                'category' => 'home-office',
                'name' => 'Canvas Tote Bag',
                'description' => 'Reusable canvas tote bag with a wide printable area.',
            ],
        ];

        $products = [];

        foreach ($data as $code => $product) {
            $products[$code] = Product::updateOrCreate(
                ['code' => $code],
                [
                    'category_id' => $categories[$product['category']]->id,
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'is_active' => true,
                ],
            );
        }

        return $products;
    }

    /**
     * @param  array<string, Product>  $products
     * @param  array<string, array<string, AttributeValue>>  $attributes
     * @return array<string, array<int, Variant>>
     */
    private function seedProductAttributesAndVariants(array $products, array $attributes): array
    {
        $variantData = [
            'TSHIRT-CLASSIC' => [
                'color' => ['white', 'black', 'navy'],
                'size' => ['s', 'm', 'l', 'xl'],
                'material' => ['cotton'],
                'print_side' => ['front', 'back'],
            ],
            'HOODIE-PREMIUM' => [
                'color' => ['black', 'navy'],
                'size' => ['m', 'l', 'xl'],
                'material' => ['fleece'],
                'print_side' => ['front', 'back'],
            ],
            'MUG-CERAMIC' => [
                'color' => ['white'],
                'material' => ['ceramic'],
                'print_side' => ['wrap'],
            ],
            'TOTE-CANVAS' => [
                'color' => ['natural', 'black'],
                'material' => ['canvas'],
                'print_side' => ['front'],
            ],
        ];

        $variants = [];

        foreach ($variantData as $productCode => $attributeCodes) {
            $product = $products[$productCode];
            $productAttributes = [];

            foreach ($attributeCodes as $attributeCode => $valueCodes) {
                $attribute = reset($attributes[$attributeCode])->attribute;
                $productAttribute = ProductAttribute::updateOrCreate(
                    ['product_id' => $product->id, 'attribute_id' => $attribute->id],
                    [
                        'is_variant_axis' => in_array($attributeCode, ['color', 'size'], true),
                        'is_required' => true,
                        'sort_order' => count($productAttributes) + 1,
                    ],
                );

                foreach ($valueCodes as $valueCode) {
                    ProductAttributeValue::updateOrCreate(
                        [
                            'product_attribute_id' => $productAttribute->id,
                            'attribute_value_id' => $attributes[$attributeCode][$valueCode]->id,
                        ],
                        ['is_active' => true],
                    );
                }

                $productAttributes[$attributeCode] = $productAttribute;
            }

            $variants[$productCode] = $this->variantsForProduct($product, $productAttributes, $attributes, $attributeCodes);
        }

        return $variants;
    }

    /**
     * @param  array<string, ProductAttribute>  $productAttributes
     * @param  array<string, array<string, AttributeValue>>  $attributes
     * @param  array<string, array<int, string>>  $attributeCodes
     * @return array<int, Variant>
     */
    private function variantsForProduct(Product $product, array $productAttributes, array $attributes, array $attributeCodes): array
    {
        $colors = $attributeCodes['color'] ?? ['standard'];
        $sizes = $attributeCodes['size'] ?? ['one-size'];
        $created = [];

        foreach ($colors as $colorCode) {
            foreach ($sizes as $sizeCode) {
                $skuParts = [$product->code, $colorCode];

                if ($sizeCode !== 'one-size') {
                    $skuParts[] = $sizeCode;
                }

                $variant = Variant::updateOrCreate(
                    ['sku' => Str::upper(implode('-', $skuParts))],
                    ['product_id' => $product->id, 'is_active' => true],
                );

                if (isset($productAttributes['color'], $attributes['color'][$colorCode])) {
                    $this->variantValue($variant, $productAttributes['color'], $attributes['color'][$colorCode]);
                }

                if ($sizeCode !== 'one-size' && isset($productAttributes['size'], $attributes['size'][$sizeCode])) {
                    $this->variantValue($variant, $productAttributes['size'], $attributes['size'][$sizeCode]);
                }

                foreach (['material', 'print_side'] as $attributeCode) {
                    if (! isset($productAttributes[$attributeCode], $attributeCodes[$attributeCode][0])) {
                        continue;
                    }

                    $valueCode = $attributeCodes[$attributeCode][0];
                    $this->variantValue($variant, $productAttributes[$attributeCode], $attributes[$attributeCode][$valueCode]);
                }

                $created[] = $variant;
            }
        }

        return $created;
    }

    /**
     * @return array<string, PrintingMethod>
     */
    private function seedPrintingMethods(): array
    {
        $data = [
            'dtg' => 'Direct to Garment',
            'sublimation' => 'Sublimation',
            'screen-print' => 'Screen Print',
            'embroidery' => 'Embroidery',
        ];

        $methods = [];

        foreach ($data as $code => $name) {
            $methods[$code] = PrintingMethod::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $methods;
    }

    /**
     * @return array<string, Provider>
     */
    private function seedProviders(): array
    {
        $data = [
            'ramallah-print-house' => [
                'name' => 'Ramallah Print House',
                'phone' => '+970599000101',
                'email' => 'catalog.ramallah@palprint.test',
                'license_number' => 'CAT-RPH-001',
            ],
            'gaza-creative-press' => [
                'name' => 'Gaza Creative Press',
                'phone' => '+970599000202',
                'email' => 'catalog.gaza@palprint.test',
                'license_number' => 'CAT-GCP-002',
            ],
        ];

        $providers = [];

        foreach ($data as $key => $provider) {
            $providers[$key] = Provider::updateOrCreate(
                ['email' => $provider['email']],
                [
                    'name' => $provider['name'],
                    'phone' => $provider['phone'],
                    'license_number' => $provider['license_number'],
                    'status' => 'active',
                ],
            );
        }

        return $providers;
    }

    /**
     * @param  array<string, Provider>  $providers
     * @param  array<string, Product>  $products
     * @param  array<string, array<int, Variant>>  $variants
     * @param  array<string, PrintingMethod>  $methods
     */
    private function seedProviderCatalog(array $providers, array $products, array $variants, array $methods): void
    {
        $offerings = [
            [
                'provider' => 'ramallah-print-house',
                'product' => 'TSHIRT-CLASSIC',
                'base_price' => 25,
                'capacity' => 120,
                'areas' => [
                    ['code' => 'front', 'name' => 'Front', 'width' => 300, 'height' => 400, 'method' => 'dtg'],
                    ['code' => 'back', 'name' => 'Back', 'width' => 320, 'height' => 420, 'method' => 'screen-print'],
                ],
            ],
            [
                'provider' => 'ramallah-print-house',
                'product' => 'MUG-CERAMIC',
                'base_price' => 18,
                'capacity' => 80,
                'areas' => [
                    ['code' => 'wrap', 'name' => 'Full Wrap', 'width' => 200, 'height' => 80, 'method' => 'sublimation'],
                ],
            ],
            [
                'provider' => 'gaza-creative-press',
                'product' => 'HOODIE-PREMIUM',
                'base_price' => 55,
                'capacity' => 60,
                'areas' => [
                    ['code' => 'front', 'name' => 'Front', 'width' => 280, 'height' => 340, 'method' => 'dtg'],
                    ['code' => 'back', 'name' => 'Back', 'width' => 320, 'height' => 380, 'method' => 'screen-print'],
                ],
            ],
            [
                'provider' => 'gaza-creative-press',
                'product' => 'TOTE-CANVAS',
                'base_price' => 22,
                'capacity' => 100,
                'areas' => [
                    ['code' => 'front', 'name' => 'Front', 'width' => 260, 'height' => 300, 'method' => 'screen-print'],
                    ['code' => 'patch', 'name' => 'Patch', 'width' => 90, 'height' => 90, 'method' => 'embroidery'],
                ],
            ],
        ];

        foreach ($offerings as $item) {
            $product = $products[$item['product']];
            $offering = ProviderOffering::updateOrCreate(
                ['provider_id' => $providers[$item['provider']]->id, 'product_id' => $product->id],
                [
                    'base_price' => $item['base_price'],
                    'currency' => 'ILS',
                    'production_time_min' => 1,
                    'production_time_max' => 3,
                    'daily_capacity' => $item['capacity'],
                    'is_active' => true,
                ],
            );

            $offeringVariants = [];

            foreach ($variants[$item['product']] as $variant) {
                $offeringVariants[] = ProviderOfferingVariant::updateOrCreate(
                    ['provider_offering_id' => $offering->id, 'variant_id' => $variant->id],
                    ['provider_sku' => "CAT-{$variant->sku}", 'is_available' => true],
                );
            }

            foreach ($item['areas'] as $areaData) {
                $area = PrintArea::updateOrCreate(
                    ['provider_offering_id' => $offering->id, 'code' => $areaData['code']],
                    [
                        'name' => $areaData['name'],
                        'max_width_mm' => $areaData['width'],
                        'max_height_mm' => $areaData['height'],
                        'is_active' => true,
                    ],
                );

                $capability = PrintCapability::updateOrCreate(
                    ['print_area_id' => $area->id, 'printing_method_id' => $methods[$areaData['method']]->id],
                    [
                        'applies_to_all_variants' => true,
                        'max_width_mm' => $areaData['width'],
                        'max_height_mm' => $areaData['height'],
                        'is_active' => true,
                    ],
                );

                foreach ($offeringVariants as $offeringVariant) {
                    $capability->variants()->firstOrCreate([
                        'provider_offering_variant_id' => $offeringVariant->id,
                    ]);
                }

                $this->pricingRule($offering, null, $capability, 1, 9, 8, 20);
                $this->pricingRule($offering, null, $capability, 10, null, 6, 10);
            }
        }
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, AttributeValue>
     */
    private function attribute(string $name, string $code, array $values): array
    {
        $attribute = Attribute::updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'data_type' => 'select', 'is_active' => true],
        );

        $created = [];

        foreach ($values as $valueCode => $value) {
            $created[$valueCode] = AttributeValue::updateOrCreate(
                ['attribute_id' => $attribute->id, 'code' => $valueCode],
                ['value' => $value, 'sort_order' => count($created) + 1],
            );
        }

        return $created;
    }

    private function variantValue(Variant $variant, ProductAttribute $productAttribute, AttributeValue $attributeValue): void
    {
        $productAttributeValue = ProductAttributeValue::updateOrCreate(
            [
                'product_attribute_id' => $productAttribute->id,
                'attribute_value_id' => $attributeValue->id,
            ],
            ['is_active' => true],
        );

        VariantValue::firstOrCreate([
            'variant_id' => $variant->id,
            'product_attribute_value_id' => $productAttributeValue->id,
        ]);
    }

    private function pricingRule(
        ProviderOffering $offering,
        ?ProviderOfferingVariant $variant,
        ?PrintCapability $capability,
        int $minQuantity,
        ?int $maxQuantity,
        int $amount,
        int $priority,
    ): void {
        PricingRule::updateOrCreate(
            [
                'provider_offering_id' => $offering->id,
                'provider_offering_variant_id' => $variant?->id,
                'print_capability_id' => $capability?->id,
                'min_quantity' => $minQuantity,
            ],
            [
                'max_quantity' => $maxQuantity,
                'pricing_type' => 'print',
                'value_type' => 'fixed',
                'amount' => $amount,
                'priority' => $priority,
                'is_active' => true,
            ],
        );
    }
}
