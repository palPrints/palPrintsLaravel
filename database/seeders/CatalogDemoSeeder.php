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
            'drinkware' => 'Drinkware',
            'office' => 'Office Printing',
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
                'gray' => 'Gray',
                'red' => 'Red',
                'green' => 'Green',
                'clear' => 'Clear',
            ]),
            'size' => $this->attribute('Size', 'size', [
                's' => 'S',
                'm' => 'M',
                'l' => 'L',
                'xl' => 'XL',
                'a4' => 'A4',
                'a5' => 'A5',
                'a3' => 'A3',
                'one-size' => 'One Size',
            ]),
            'material' => $this->attribute('Material', 'material', [
                'cotton' => 'Cotton',
                'fleece' => 'Fleece',
                'ceramic' => 'Ceramic',
                'canvas' => 'Canvas',
                'cotton-twill' => 'Cotton Twill',
                'polyester' => 'Polyester',
                'polycarbonate' => 'Polycarbonate',
                'paper' => 'Paper',
                'cardstock' => 'Cardstock',
                'vinyl' => 'Vinyl',
            ]),
            'print_side' => $this->attribute('Print Side', 'print_side', [
                'front' => 'Front',
                'back' => 'Back',
                'wrap' => 'Full Wrap',
                'cover' => 'Cover',
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
            'TSHIRT-CLASSIC' => ['apparel', 'Classic T-Shirt', 'Cotton unisex t-shirt for everyday custom printing.', 'front/assets/images/customer/products/1.png', true],
            'HOODIE-PREMIUM' => ['apparel', 'Premium Hoodie', 'Warm fleece hoodie with front and back print support.', 'front/assets/images/customer/products/2.png', true],
            'PAPER-PRINT' => ['office', 'Paper Printing', 'Document and flyer printing for everyday needs.', 'front/assets/images/customer/products/8.png', false],
            'STICKER-CUSTOM' => ['office', 'Custom Sticker', 'Vinyl stickers for packaging, laptops, and branding.', 'front/assets/images/customer/products/11.png', true],
            'MUG-CERAMIC' => ['drinkware', 'Ceramic Mug', '330 ml ceramic mug for full-color sublimation.', 'front/assets/images/customer/products/7.png', true],
            'CAP-CLASSIC' => ['apparel', 'Classic Cap', 'Adjustable cap ready for front embroidery or print.', 'front/assets/images/customer/products/3.png', false],
            'TOTE-CANVAS' => ['accessories', 'Canvas Tote Bag', 'Reusable canvas tote bag with a wide printable area.', 'front/assets/images/customer/products/4.png', false],
            'SCARF-CUSTOM' => ['accessories', 'Custom Scarf', 'Soft scarf for personalized artwork and branding.', 'front/assets/images/customer/products/5.png', false],
            'PHONE-CASE' => ['accessories', 'Phone Case', 'Protective phone case with printable back panel.', 'front/assets/images/customer/products/6.png', false],
            'NOTEBOOK-CUSTOM' => ['office', 'Custom Notebook', 'Notebook with a printable custom cover.', 'front/assets/images/customer/products/9.png', false],
            'POSTER-PRINT' => ['office', 'Poster Print', 'Large format poster printing for artwork and campaigns.', 'front/assets/images/customer/products/10.png', false],
            'WEDDING-CARDS' => ['office', 'Wedding Cards', 'Printed wedding invitation cards on premium cardstock.', 'front/assets/images/customer/products/12.png', false],
        ];

        $products = [];

        foreach ($data as $code => [$category, $name, $description, $image, $isActive]) {
            $products[$code] = Product::updateOrCreate(
                ['code' => $code],
                [
                    'category_id' => $categories[$category]->id,
                    'name' => $name,
                    'description' => $description,
                    'image' => $image,
                    'is_active' => $isActive,
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
            'TSHIRT-CLASSIC' => ['color' => ['white', 'black', 'navy'], 'size' => ['s', 'm', 'l', 'xl'], 'material' => ['cotton'], 'print_side' => ['front', 'back']],
            'HOODIE-PREMIUM' => ['color' => ['black', 'navy'], 'size' => ['m', 'l', 'xl'], 'material' => ['fleece'], 'print_side' => ['front', 'back']],
            'MUG-CERAMIC' => ['color' => ['white'], 'size' => ['one-size'], 'material' => ['ceramic'], 'print_side' => ['wrap']],
            'STICKER-CUSTOM' => ['color' => ['white', 'clear'], 'size' => ['one-size'], 'material' => ['vinyl'], 'print_side' => ['front']],
            'PAPER-PRINT' => ['color' => ['white'], 'size' => ['a4', 'a5'], 'material' => ['paper'], 'print_side' => ['front']],
            'CAP-CLASSIC' => ['color' => ['black', 'navy', 'gray'], 'size' => ['one-size'], 'material' => ['cotton-twill'], 'print_side' => ['front']],
            'TOTE-CANVAS' => ['color' => ['natural', 'black'], 'size' => ['one-size'], 'material' => ['canvas'], 'print_side' => ['front']],
            'SCARF-CUSTOM' => ['color' => ['white', 'black', 'red', 'green'], 'size' => ['one-size'], 'material' => ['polyester'], 'print_side' => ['front']],
            'PHONE-CASE' => ['color' => ['clear', 'black'], 'size' => ['one-size'], 'material' => ['polycarbonate'], 'print_side' => ['back']],
            'NOTEBOOK-CUSTOM' => ['color' => ['white', 'black', 'navy'], 'size' => ['a5'], 'material' => ['paper'], 'print_side' => ['cover']],
            'POSTER-PRINT' => ['color' => ['white'], 'size' => ['a3'], 'material' => ['paper'], 'print_side' => ['front']],
            'WEDDING-CARDS' => ['color' => ['white'], 'size' => ['one-size'], 'material' => ['cardstock'], 'print_side' => ['front']],
        ];

        $variants = [];

        foreach ($variantData as $productCode => $attributeCodes) {
            $productAttributes = [];

            foreach ($attributeCodes as $attributeCode => $valueCodes) {
                $attribute = reset($attributes[$attributeCode])->attribute;
                $productAttribute = ProductAttribute::updateOrCreate(
                    ['product_id' => $products[$productCode]->id, 'attribute_id' => $attribute->id],
                    [
                        'is_variant_axis' => in_array($attributeCode, ['color', 'size'], true),
                        'is_required' => true,
                        'sort_order' => count($productAttributes) + 1,
                    ],
                );

                foreach ($valueCodes as $valueCode) {
                    ProductAttributeValue::updateOrCreate(
                        ['product_attribute_id' => $productAttribute->id, 'attribute_value_id' => $attributes[$attributeCode][$valueCode]->id],
                        ['is_active' => true],
                    );
                }

                $productAttributes[$attributeCode] = $productAttribute;
            }

            $variants[$productCode] = $this->variantsForProduct($products[$productCode], $productAttributes, $attributes, $attributeCodes);
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
        $created = [];

        foreach ($attributeCodes['color'] ?? ['standard'] as $colorCode) {
            foreach ($attributeCodes['size'] ?? ['one-size'] as $sizeCode) {
                $sku = Str::upper($product->code.'-'.$colorCode.($sizeCode !== 'one-size' ? '-'.$sizeCode : ''));
                $variant = Variant::updateOrCreate(['sku' => $sku], ['product_id' => $product->id, 'is_active' => true]);

                if (isset($productAttributes['color'])) {
                    $this->variantValue($variant, $productAttributes['color'], $attributes['color'][$colorCode]);
                }

                if (isset($productAttributes['size'])) {
                    $this->variantValue($variant, $productAttributes['size'], $attributes['size'][$sizeCode]);
                }

                foreach (['material', 'print_side'] as $attributeCode) {
                    $valueCode = $attributeCodes[$attributeCode][0] ?? null;
                    if ($valueCode && isset($productAttributes[$attributeCode])) {
                        $this->variantValue($variant, $productAttributes[$attributeCode], $attributes[$attributeCode][$valueCode]);
                    }
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
        $methods = [];

        foreach (['dtg' => 'Direct to Garment', 'sublimation' => 'Sublimation', 'screen-print' => 'Screen Print', 'embroidery' => 'Embroidery'] as $code => $name) {
            $methods[$code] = PrintingMethod::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }

        return $methods;
    }

    /**
     * @return array<string, Provider>
     */
    private function seedProviders(): array
    {
        return [
            'default' => Provider::updateOrCreate(
                ['email' => 'catalog.provider@palprint.test'],
                ['name' => 'PalPrints Catalog Provider', 'phone' => '+970599000000', 'license_number' => 'CAT-DEMO-001', 'status' => 'active'],
            ),
        ];
    }

    /**
     * @param  array<string, Provider>  $providers
     * @param  array<string, Product>  $products
     * @param  array<string, array<int, Variant>>  $variants
     * @param  array<string, PrintingMethod>  $methods
     */
    private function seedProviderCatalog(array $providers, array $products, array $variants, array $methods): void
    {
        $prices = [
            'TSHIRT-CLASSIC' => [15, [['front', 'Front', 300, 400, 'dtg'], ['back', 'Back', 320, 420, 'screen-print']]],
            'HOODIE-PREMIUM' => [35, [['front', 'Front', 280, 340, 'dtg'], ['back', 'Back', 320, 380, 'screen-print']]],
            'MUG-CERAMIC' => [12, [['wrap', 'Full Wrap', 200, 80, 'sublimation']]],
            'STICKER-CUSTOM' => [8, [['front', 'Front', 100, 100, 'screen-print']]],
            'PAPER-PRINT' => [10, [['front', 'Front', 210, 297, 'screen-print']]],
            'CAP-CLASSIC' => [20, [['front', 'Front', 120, 60, 'embroidery']]],
            'TOTE-CANVAS' => [50, [['front', 'Front', 260, 300, 'screen-print']]],
            'SCARF-CUSTOM' => [18, [['front', 'Front', 280, 120, 'sublimation']]],
            'PHONE-CASE' => [25, [['back', 'Back', 75, 150, 'sublimation']]],
            'NOTEBOOK-CUSTOM' => [15, [['cover', 'Cover', 148, 210, 'screen-print']]],
            'POSTER-PRINT' => [20, [['front', 'Front', 297, 420, 'screen-print']]],
            'WEDDING-CARDS' => [30, [['front', 'Front', 150, 210, 'screen-print']]],
        ];

        foreach ($prices as $productCode => [$basePrice, $areas]) {
            $offering = ProviderOffering::updateOrCreate(
                ['provider_id' => $providers['default']->id, 'product_id' => $products[$productCode]->id],
                ['base_price' => $basePrice, 'currency' => 'ILS', 'production_time_min' => 1, 'production_time_max' => 3, 'daily_capacity' => 100, 'is_active' => true],
            );

            $offeringVariants = collect($variants[$productCode])
                ->map(fn (Variant $variant) => ProviderOfferingVariant::updateOrCreate(
                    ['provider_offering_id' => $offering->id, 'variant_id' => $variant->id],
                    ['provider_sku' => 'CAT-'.$variant->sku, 'is_available' => true],
                ));

            foreach ($areas as [$code, $name, $width, $height, $method]) {
                $area = PrintArea::updateOrCreate(
                    ['provider_offering_id' => $offering->id, 'code' => $code],
                    ['name' => $name, 'max_width_mm' => $width, 'max_height_mm' => $height, 'is_active' => true],
                );

                $capability = PrintCapability::updateOrCreate(
                    ['print_area_id' => $area->id, 'printing_method_id' => $methods[$method]->id],
                    ['applies_to_all_variants' => true, 'max_width_mm' => $width, 'max_height_mm' => $height, 'is_active' => true],
                );

                $offeringVariants->each(fn (ProviderOfferingVariant $variant) => $capability->variants()->firstOrCreate([
                    'provider_offering_variant_id' => $variant->id,
                ]));

                $this->pricingRule($offering, $capability, 1, 9, 8, 20);
                $this->pricingRule($offering, $capability, 10, null, 6, 10);
            }
        }
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, AttributeValue>
     */
    private function attribute(string $name, string $code, array $values): array
    {
        $attribute = Attribute::updateOrCreate(['code' => $code], ['name' => $name, 'data_type' => 'select', 'is_active' => true]);
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
            ['product_attribute_id' => $productAttribute->id, 'attribute_value_id' => $attributeValue->id],
            ['is_active' => true],
        );

        VariantValue::firstOrCreate(['variant_id' => $variant->id, 'product_attribute_value_id' => $productAttributeValue->id]);
    }

    private function pricingRule(ProviderOffering $offering, PrintCapability $capability, int $minQuantity, ?int $maxQuantity, int $amount, int $priority): void
    {
        PricingRule::updateOrCreate(
            ['provider_offering_id' => $offering->id, 'print_capability_id' => $capability->id, 'min_quantity' => $minQuantity],
            ['max_quantity' => $maxQuantity, 'pricing_type' => 'print', 'value_type' => 'fixed', 'amount' => $amount, 'priority' => $priority, 'is_active' => true],
        );
    }
}
