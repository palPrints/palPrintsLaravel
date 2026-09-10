<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\DesignerProfile;
use App\Models\PricingRule;
use App\Models\PrintArea;
use App\Models\PrintCapability;
use App\Models\PrintingMethod;
use App\Models\PrintProvider;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Provider;
use App\Models\ProviderOffering;
use App\Models\ProviderOfferingVariant;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->user('Demo Admin', 'admin@palprint.test', 'admin');
        $customer = $this->user('Demo Customer', 'customer@palprint.test', 'customer');
        $designer = $this->user('Lina Designer', 'designer@palprint.test', 'designer');
        $printProviderUser = $this->user('Demo Print Provider', 'printer@palprint.test', 'print_provider');
        $this->user('Demo Delivery', 'delivery@palprint.test', 'delivery_partner');

        DesignerProfile::updateOrCreate(['user_id' => $designer->id], [
            'full_name' => 'Lina Designer',
            'bio' => 'مصممة تجريبية متخصصة في الرسومات المخصصة للملابس.',
            'skills' => ['illustration', 'typography', 't-shirt design'],
            'portfolio_url' => 'https://example.test/designers/lina',
            'approval_status' => 'approved',
            'profile_completed_at' => now(),
            'submitted_at' => now()->subDays(3),
            'reviewed_at' => now()->subDays(2),
            'approved_at' => now()->subDays(2),
            'approved_by' => $admin->id,
            'total_sales' => 12,
            'total_earnings' => 480,
        ]);

        PrintProvider::updateOrCreate(['user_id' => $printProviderUser->id], [
            'company_name' => 'Demo Print House',
            'address' => 'Ramallah, Palestine',
            'phone' => '+970599000001',
            'whatsapp_number' => '+970599000001',
            'working_hours' => ['sun-thu' => '09:00-17:00'],
            'approval_status' => 'approved',
            'profile_completed_at' => now(),
            'submitted_at' => now()->subDays(3),
            'reviewed_at' => now()->subDays(2),
            'approved_at' => now()->subDays(2),
            'approved_by' => $admin->id,
            'total_orders' => 18,
            'total_earnings' => 720,
            'is_active' => true,
        ]);

        $apparel = Category::updateOrCreate(['slug' => 'apparel'], ['name' => 'Apparel', 'parent_id' => null, 'is_active' => true]);
        $accessories = Category::updateOrCreate(['slug' => 'accessories'], ['name' => 'Accessories', 'parent_id' => null, 'is_active' => true]);

        $tShirt = Product::updateOrCreate(['code' => 'TSHIRT-CLASSIC'], [
            'category_id' => $apparel->id,
            'name' => 'Classic T-Shirt',
            'description' => 'Cotton unisex t-shirt for custom printing.',
            'is_active' => true,
        ]);
        $mug = Product::updateOrCreate(['code' => 'MUG-CERAMIC'], [
            'category_id' => $accessories->id,
            'name' => 'Ceramic Mug',
            'description' => '330 ml ceramic mug for full-color printing.',
            'is_active' => true,
        ]);

        $color = $this->attribute('Color', 'color', ['white' => 'White', 'black' => 'Black']);
        $size = $this->attribute('Size', 'size', ['s' => 'S', 'm' => 'M', 'l' => 'L']);

        $tShirtColor = $this->productAttribute($tShirt, $color, true, true, 1);
        $tShirtSize = $this->productAttribute($tShirt, $size, true, true, 2);
        $mugColor = $this->productAttribute($mug, $color, false, false, 1);

        $variants = [];
        foreach (['white', 'black'] as $colorCode) {
            foreach (['s', 'm', 'l'] as $sizeCode) {
                $variant = Variant::updateOrCreate(['sku' => "TSHIRT-{$colorCode}-{$sizeCode}"], [
                    'product_id' => $tShirt->id,
                    'is_active' => true,
                ]);
                $this->variantValue($variant, $tShirtColor, $color[$colorCode]);
                $this->variantValue($variant, $tShirtSize, $size[$sizeCode]);
                $variants[] = $variant;
            }
        }
        $mugVariant = Variant::updateOrCreate(['sku' => 'MUG-CERAMIC-WHITE'], ['product_id' => $mug->id, 'is_active' => true]);
        $this->variantValue($mugVariant, $mugColor, $color['white']);

        $dtg = PrintingMethod::updateOrCreate(['code' => 'dtg'], ['name' => 'Direct to Garment', 'is_active' => true]);
        $sublimation = PrintingMethod::updateOrCreate(['code' => 'sublimation'], ['name' => 'Sublimation', 'is_active' => true]);
        $provider = Provider::updateOrCreate(['email' => 'catalog-provider@palprint.test'], [
            'name' => 'Demo Print House', 'phone' => '+970599000001', 'license_number' => 'DEMO-PRINT-001', 'status' => 'active',
        ]);

        $tShirtOffering = $this->offering($provider, $tShirt, 25, 'ILS', 1, 2, 100);
        $mugOffering = $this->offering($provider, $mug, 18, 'ILS', 1, 2, 75);

        $tShirtOfferingVariants = array_map(fn (Variant $variant) => ProviderOfferingVariant::updateOrCreate(
            ['provider_offering_id' => $tShirtOffering->id, 'variant_id' => $variant->id],
            ['provider_sku' => "DEMO-{$variant->sku}", 'is_available' => true],
        ), $variants);
        $mugOfferingVariant = ProviderOfferingVariant::updateOrCreate(
            ['provider_offering_id' => $mugOffering->id, 'variant_id' => $mugVariant->id],
            ['provider_sku' => 'DEMO-MUG-WHITE', 'is_available' => true],
        );

        $tShirtArea = $this->printArea($tShirtOffering, 'front', 'Front', 300, 400);
        $mugArea = $this->printArea($mugOffering, 'wrap', 'Full wrap', 200, 80);
        $tShirtCapability = $this->capability($tShirtArea, $dtg, 300, 400);
        $mugCapability = $this->capability($mugArea, $sublimation, 200, 80);

        foreach ($tShirtOfferingVariants as $offeringVariant) {
            $this->capabilityVariant($tShirtCapability, $offeringVariant);
        }
        $this->capabilityVariant($mugCapability, $mugOfferingVariant);

        $this->pricingRule($tShirtOffering, null, $tShirtCapability, 1, 9, 'print', 'fixed', 8, 10);
        $this->pricingRule($tShirtOffering, null, $tShirtCapability, 10, null, 'print', 'fixed', 6, 10);
        $this->pricingRule($mugOffering, $mugOfferingVariant, $mugCapability, 1, null, 'print', 'fixed', 7, 10);

        $this->command?->info('Demo data seeded. Demo account password: password');
    }

    private function user(string $name, string $email, string $role): User
    {
        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name, 'password' => Hash::make('password'), 'email_verified_at' => now(), 'is_active' => true,
        ]);
        $user->syncRoles([$role]);

        return $user;
    }

    /** @return array<string, AttributeValue> */
    private function attribute(string $name, string $code, array $values): array
    {
        $attribute = Attribute::updateOrCreate(['code' => $code], ['name' => $name, 'data_type' => 'select', 'is_active' => true]);
        $result = [];
        foreach ($values as $valueCode => $value) {
            $result[$valueCode] = AttributeValue::updateOrCreate(
                ['attribute_id' => $attribute->id, 'code' => $valueCode], ['value' => $value, 'sort_order' => count($result) + 1],
            );
        }

        return $result;
    }

    private function productAttribute(Product $product, array $values, bool $isVariantAxis, bool $isRequired, int $sortOrder): ProductAttribute
    {
        $attribute = reset($values)->attribute;

        return ProductAttribute::updateOrCreate(['product_id' => $product->id, 'attribute_id' => $attribute->id], [
            'is_variant_axis' => $isVariantAxis, 'is_required' => $isRequired, 'sort_order' => $sortOrder,
        ]);
    }

    private function variantValue(Variant $variant, ProductAttribute $productAttribute, AttributeValue $attributeValue): void
    {
        $productAttributeValue = ProductAttributeValue::updateOrCreate(
            ['product_attribute_id' => $productAttribute->id, 'attribute_value_id' => $attributeValue->id], ['is_active' => true],
        );
        VariantValue::firstOrCreate(['variant_id' => $variant->id, 'product_attribute_value_id' => $productAttributeValue->id]);
    }

    private function offering(Provider $provider, Product $product, int $basePrice, string $currency, int $minDays, int $maxDays, int $capacity): ProviderOffering
    {
        return ProviderOffering::updateOrCreate(['provider_id' => $provider->id, 'product_id' => $product->id], [
            'base_price' => $basePrice, 'currency' => $currency, 'production_time_min' => $minDays,
            'production_time_max' => $maxDays, 'daily_capacity' => $capacity, 'is_active' => true,
        ]);
    }

    private function printArea(ProviderOffering $offering, string $code, string $name, int $width, int $height): PrintArea
    {
        return PrintArea::updateOrCreate(['provider_offering_id' => $offering->id, 'code' => $code], [
            'name' => $name, 'max_width_mm' => $width, 'max_height_mm' => $height, 'is_active' => true,
        ]);
    }

    private function capability(PrintArea $area, PrintingMethod $method, int $width, int $height): PrintCapability
    {
        return PrintCapability::updateOrCreate(['print_area_id' => $area->id, 'printing_method_id' => $method->id], [
            'applies_to_all_variants' => true, 'max_width_mm' => $width, 'max_height_mm' => $height, 'is_active' => true,
        ]);
    }

    private function capabilityVariant(PrintCapability $capability, ProviderOfferingVariant $variant): void
    {
        $capability->variants()->firstOrCreate(['provider_offering_variant_id' => $variant->id]);
    }

    private function pricingRule(ProviderOffering $offering, ?ProviderOfferingVariant $variant, PrintCapability $capability, int $minQuantity, ?int $maxQuantity, string $type, string $valueType, int $amount, int $priority): void
    {
        PricingRule::updateOrCreate([
            'provider_offering_id' => $offering->id, 'provider_offering_variant_id' => $variant?->id,
            'print_capability_id' => $capability->id, 'min_quantity' => $minQuantity,
        ], [
            'max_quantity' => $maxQuantity, 'pricing_type' => $type, 'value_type' => $valueType,
            'amount' => $amount, 'priority' => $priority, 'is_active' => true,
        ]);
    }
}
