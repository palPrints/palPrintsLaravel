<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\BranchOfferingVariant;
use App\Models\BranchPricingRule;
use App\Models\BranchPrintArea;
use App\Models\BranchPrintCapability;
use App\Models\BranchProductOffering;
use App\Models\Category;
use App\Models\PrintingMethod;
use App\Models\PrintProvider;
use App\Models\PrintProviderBranch;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
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
        $this->seedProductAttributesAndVariants($products, $attributes);
        $this->seedBranchCatalog($products);
        $this->seedBranchPrintConfiguration();
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
     * @param  array<string, Product>  $products
     */
    private function seedBranchCatalog(array $products): void
    {
        $printProvider = PrintProvider::query()->first();

        if (! $printProvider) {
            return;
        }

        $branches = [
            'gaza' => PrintProviderBranch::updateOrCreate(
                ['print_provider_id' => $printProvider->id, 'name' => 'Gaza Branch'],
                ['city' => 'Gaza', 'region' => 'Gaza Strip', 'address' => 'Gaza', 'phone' => $printProvider->phone, 'working_hours' => null, 'is_active' => true],
            ),
            'west_bank' => PrintProviderBranch::updateOrCreate(
                ['print_provider_id' => $printProvider->id, 'name' => 'West Bank Branch'],
                ['city' => 'Ramallah', 'region' => 'West Bank', 'address' => 'Ramallah', 'phone' => $printProvider->phone, 'working_hours' => null, 'is_active' => true],
            ),
        ];

        $offerings = [
            'gaza' => [
                'TSHIRT-CLASSIC' => [15, 120],
                'HOODIE-PREMIUM' => [35, 60],
                'MUG-CERAMIC' => [12, 80],
                'STICKER-CUSTOM' => [8, 250],
                'PAPER-PRINT' => [10, 300],
                'CAP-CLASSIC' => [20, 90],
                'TOTE-CANVAS' => [50, 100],
                'PHONE-CASE' => [25, 110],
                'POSTER-PRINT' => [20, 100],
                'WEDDING-CARDS' => [30, 120],
            ],
            'west_bank' => [
                'TSHIRT-CLASSIC' => [16, 100],
                'HOODIE-PREMIUM' => [36, 55],
                'MUG-CERAMIC' => [13, 75],
                'STICKER-CUSTOM' => [9, 220],
                'PAPER-PRINT' => [11, 280],
                'CAP-CLASSIC' => [21, 80],
                'TOTE-CANVAS' => [52, 90],
                'SCARF-CUSTOM' => [18, 70],
                'NOTEBOOK-CUSTOM' => [15, 140],
                'POSTER-PRINT' => [21, 90],
            ],
        ];

        foreach ($offerings as $branchKey => $branchOfferings) {
            foreach ($branchOfferings as $productCode => [$basePrice, $dailyCapacity]) {
                if (! isset($products[$productCode])) {
                    continue;
                }

                BranchProductOffering::updateOrCreate(
                    ['print_provider_branch_id' => $branches[$branchKey]->id, 'product_id' => $products[$productCode]->id],
                    [
                        'base_price' => $basePrice,
                        'currency' => 'ILS',
                        'production_time_min' => 1,
                        'production_time_max' => 3,
                        'daily_capacity' => $dailyCapacity,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    private function seedBranchPrintConfiguration(): void
    {
        $methods = $this->seedPrintingMethods();

        BranchProductOffering::query()
            ->with(['product.variants', 'printProviderBranch'])
            ->get()
            ->each(function (BranchProductOffering $offering) use ($methods): void {
                $this->seedBranchOfferingVariants($offering);
                $this->seedPrintAreasAndCapabilities($offering, $methods);
                $this->seedPricingRules($offering);
            });
    }

    /**
     * @return array<string, PrintingMethod>
     */
    private function seedPrintingMethods(): array
    {
        $data = [
            'dtf' => 'DTF Printing',
            'dtg' => 'DTG Printing',
            'sublimation' => 'Sublimation',
            'embroidery' => 'Embroidery',
            'digital-paper' => 'Digital Paper Printing',
            'vinyl-cut' => 'Vinyl Cut',
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

    private function seedBranchOfferingVariants(BranchProductOffering $offering): void
    {
        $offering->product->variants->each(function (Variant $variant) use ($offering): void {
            BranchOfferingVariant::updateOrCreate(
                ['branch_product_offering_id' => $offering->id, 'variant_id' => $variant->id],
                [
                    'branch_sku' => $offering->printProviderBranch->id.'-'.$variant->sku,
                    'is_available' => $variant->is_active && $offering->is_active,
                ],
            );
        });
    }

    /**
     * @param  array<string, PrintingMethod>  $methods
     */
    private function seedPrintAreasAndCapabilities(BranchProductOffering $offering, array $methods): void
    {
        foreach ($this->printAreaDefinitions($offering->product->code) as $areaCode => $areaData) {
            $area = BranchPrintArea::updateOrCreate(
                ['branch_product_offering_id' => $offering->id, 'code' => $areaCode],
                [
                    'name' => $areaData['name'],
                    'max_width_mm' => $areaData['width'],
                    'max_height_mm' => $areaData['height'],
                    'is_active' => true,
                ],
            );

            foreach ($areaData['methods'] as $methodCode) {
                if (! isset($methods[$methodCode])) {
                    continue;
                }

                $appliesToAllVariants = ! ($offering->product->code === 'TSHIRT-CLASSIC' && $methodCode === 'dtg');
                $capability = BranchPrintCapability::updateOrCreate(
                    ['branch_print_area_id' => $area->id, 'printing_method_id' => $methods[$methodCode]->id],
                    [
                        'applies_to_all_variants' => $appliesToAllVariants,
                        'max_width_mm' => $areaData['width'],
                        'max_height_mm' => $areaData['height'],
                        'is_active' => true,
                    ],
                );

                if (! $appliesToAllVariants) {
                    $this->seedCapabilityVariantsForWhiteSkus($capability, $offering);
                }
            }
        }
    }

    /**
     * @return array<string, array{name: string, width: int, height: int, methods: array<int, string>}>
     */
    private function printAreaDefinitions(string $productCode): array
    {
        return match ($productCode) {
            'TSHIRT-CLASSIC' => [
                'front' => ['name' => 'Front Chest', 'width' => 280, 'height' => 350, 'methods' => ['dtf', 'dtg']],
                'back' => ['name' => 'Back Print', 'width' => 300, 'height' => 380, 'methods' => ['dtf']],
            ],
            'HOODIE-PREMIUM' => [
                'front' => ['name' => 'Front Chest', 'width' => 260, 'height' => 300, 'methods' => ['dtf', 'embroidery']],
                'back' => ['name' => 'Back Print', 'width' => 300, 'height' => 360, 'methods' => ['dtf']],
            ],
            'MUG-CERAMIC' => [
                'wrap' => ['name' => 'Full Wrap', 'width' => 210, 'height' => 90, 'methods' => ['sublimation']],
            ],
            'STICKER-CUSTOM' => [
                'front' => ['name' => 'Sticker Face', 'width' => 150, 'height' => 150, 'methods' => ['vinyl-cut', 'digital-paper']],
            ],
            'PAPER-PRINT' => [
                'front' => ['name' => 'Front Page', 'width' => 210, 'height' => 297, 'methods' => ['digital-paper']],
            ],
            'CAP-CLASSIC' => [
                'front' => ['name' => 'Front Panel', 'width' => 120, 'height' => 60, 'methods' => ['embroidery', 'dtf']],
            ],
            'TOTE-CANVAS' => [
                'front' => ['name' => 'Bag Front', 'width' => 280, 'height' => 320, 'methods' => ['dtf', 'embroidery']],
            ],
            'PHONE-CASE' => [
                'back' => ['name' => 'Back Panel', 'width' => 70, 'height' => 150, 'methods' => ['sublimation']],
            ],
            'SCARF-CUSTOM' => [
                'front' => ['name' => 'Scarf Panel', 'width' => 300, 'height' => 120, 'methods' => ['sublimation']],
            ],
            'NOTEBOOK-CUSTOM' => [
                'cover' => ['name' => 'Cover', 'width' => 148, 'height' => 210, 'methods' => ['digital-paper']],
            ],
            'POSTER-PRINT' => [
                'front' => ['name' => 'Poster Face', 'width' => 297, 'height' => 420, 'methods' => ['digital-paper']],
            ],
            'WEDDING-CARDS' => [
                'front' => ['name' => 'Card Face', 'width' => 150, 'height' => 210, 'methods' => ['digital-paper']],
            ],
            default => [
                'front' => ['name' => 'Front', 'width' => 200, 'height' => 200, 'methods' => ['dtf']],
            ],
        };
    }

    private function seedCapabilityVariantsForWhiteSkus(BranchPrintCapability $capability, BranchProductOffering $offering): void
    {
        BranchOfferingVariant::query()
            ->where('branch_product_offering_id', $offering->id)
            ->whereHas('variant', fn ($query) => $query->where('sku', 'like', '%-WHITE-%'))
            ->get()
            ->each(function (BranchOfferingVariant $branchVariant) use ($capability): void {
                $capability->branchPrintCapabilityVariants()->updateOrCreate(
                    ['branch_offering_variant_id' => $branchVariant->id],
                    [],
                );
            });
    }

    private function seedPricingRules(BranchProductOffering $offering): void
    {
        BranchPricingRule::updateOrCreate(
            [
                'branch_product_offering_id' => $offering->id,
                'branch_offering_variant_id' => null,
                'branch_print_capability_id' => null,
                'pricing_type' => 'base',
                'min_quantity' => 1,
                'max_quantity' => null,
            ],
            [
                'value_type' => 'fixed',
                'amount' => $offering->base_price,
                'priority' => 10,
                'is_active' => true,
                'valid_from' => null,
                'valid_until' => null,
            ],
        );

        $offering->branchPrintAreas()
            ->with('branchPrintCapabilities.printingMethod')
            ->get()
            ->flatMap(fn (BranchPrintArea $area) => $area->branchPrintCapabilities)
            ->each(function (BranchPrintCapability $capability) use ($offering): void {
                BranchPricingRule::updateOrCreate(
                    [
                        'branch_product_offering_id' => $offering->id,
                        'branch_offering_variant_id' => null,
                        'branch_print_capability_id' => $capability->id,
                        'pricing_type' => 'print_method_addon',
                        'min_quantity' => 1,
                        'max_quantity' => null,
                    ],
                    [
                        'value_type' => 'fixed',
                        'amount' => $this->methodAddonAmount($capability->printingMethod->code),
                        'priority' => 20,
                        'is_active' => true,
                        'valid_from' => null,
                        'valid_until' => null,
                    ],
                );
            });

        BranchPricingRule::updateOrCreate(
            [
                'branch_product_offering_id' => $offering->id,
                'branch_offering_variant_id' => null,
                'branch_print_capability_id' => null,
                'pricing_type' => 'quantity_discount',
                'min_quantity' => 10,
                'max_quantity' => null,
            ],
            [
                'value_type' => 'percent',
                'amount' => 8,
                'priority' => 30,
                'is_active' => true,
                'valid_from' => null,
                'valid_until' => null,
            ],
        );
    }

    private function methodAddonAmount(string $methodCode): int
    {
        return match ($methodCode) {
            'embroidery' => 12,
            'sublimation' => 6,
            'dtg' => 8,
            'dtf' => 5,
            'vinyl-cut' => 4,
            'digital-paper' => 2,
            default => 0,
        };
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
}