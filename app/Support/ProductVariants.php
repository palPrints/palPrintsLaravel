<?php

namespace App\Support;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Variant;
use App\Models\VariantValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The colours and sizes of a product as the admin edits them, and the variants (colour x size) built from them.
 *
 * The admin adds colours and sizes with the product; the print shops then pick which of them they offer.
 * Nothing is ever deleted: a colour or size that is taken off, and the variants that used it, are only switched off,
 * because carts and orders may still point at them. Adding it back switches them on again.
 */
class ProductVariants
{
    /** Words that mean "a single size" in the form. */
    private const ONE_SIZE_WORDS = ['مقاس واحد', 'قياسي', 'مقاس موحد', 'one size', 'one-size', 'standard'];

    /**
     * What the form shows when editing: every active colour and size, whatever the shops offer.
     *
     * @return array{colors: array<int, array{code: string, name: string, hex: string}>, sizes: array<int, string>}
     */
    public static function options(Product $product): array
    {
        $product->loadMissing('attributes.attribute', 'attributes.values.attributeValue');

        $values = fn (string $axis) => $product->attributes
            ->first(fn (ProductAttribute $attribute) => $attribute->attribute?->code === $axis)
            ?->values->where('is_active', true)->pluck('attributeValue')->filter()->sortBy('sort_order')->values()
            ?? collect();

        return [
            'colors' => $values('color')->map(fn (AttributeValue $value) => ['code' => $value->code] + CatalogProductData::describeColor($value))->all(),
            'sizes' => $values('size')->map(fn (AttributeValue $value) => self::sizeLabel($value))->all(),
        ];
    }

    /**
     * Makes the product's colours and sizes (and so its variants) match the lists.
     *
     * @param  array<int, array{code?: ?string, name: string, hex: string}>  $colors
     * @param  array<int, string>  $sizes  labels such as "S", "XL" or "مقاس واحد"
     */
    public static function sync(Product $product, array $colors, array $sizes): void
    {
        DB::transaction(function () use ($product, $colors, $sizes) {
            $colorAttribute = Attribute::firstOrCreate(['code' => 'color'], ['name' => 'Color', 'data_type' => 'select', 'is_active' => true]);
            $sizeAttribute = Attribute::firstOrCreate(['code' => 'size'], ['name' => 'Size', 'data_type' => 'select', 'is_active' => true]);

            $colorAxis = self::axis($product, $colorAttribute, 1);
            $sizeAxis = self::axis($product, $sizeAttribute, 2);

            $colorValues = collect($colors)->map(fn (array $color) => self::colorValue($colorAttribute, $color))->unique('id')->values();
            $sizeValues = collect($sizes)->map(fn (string $label) => self::sizeValue($sizeAttribute, $label))->unique('id')->values();

            $colorPav = self::activate($colorAxis, $colorValues);
            $sizePav = self::activate($sizeAxis, $sizeValues);

            self::syncVariants($product, $colorPav, $sizePav);
        });
    }

    /** The product's row for an attribute (colour or size), created on first use. */
    private static function axis(Product $product, Attribute $attribute, int $order): ProductAttribute
    {
        return ProductAttribute::firstOrCreate(
            ['product_id' => $product->id, 'attribute_id' => $attribute->id],
            ['is_variant_axis' => true, 'is_required' => true, 'sort_order' => $order],
        );
    }

    /** A colour the admin picked: an existing one by its code, or a new one named by the admin. */
    private static function colorValue(Attribute $attribute, array $color): AttributeValue
    {
        if (! empty($color['code'])) {
            $existing = AttributeValue::where('attribute_id', $attribute->id)->where('code', $color['code'])->first();
            if ($existing) {
                return $existing;
            }
        }

        $hex = strtolower($color['hex']);
        $code = 'c'.ltrim($hex, '#');

        return AttributeValue::firstOrCreate(
            ['attribute_id' => $attribute->id, 'code' => $code],
            [
                'value' => json_encode(['name' => trim($color['name']), 'hex' => $hex], JSON_UNESCAPED_UNICODE),
                'sort_order' => (int) AttributeValue::where('attribute_id', $attribute->id)->max('sort_order') + 1,
            ],
        );
    }

    private static function sizeValue(Attribute $attribute, string $label): AttributeValue
    {
        $code = self::sizeCode($label);

        return AttributeValue::firstOrCreate(
            ['attribute_id' => $attribute->id, 'code' => $code],
            [
                'value' => $code === 'one-size' ? 'One Size' : mb_strtoupper(trim($label)),
                'sort_order' => (int) AttributeValue::where('attribute_id', $attribute->id)->max('sort_order') + 1,
            ],
        );
    }

    public static function sizeCode(string $label): string
    {
        $label = mb_strtolower(trim($label));

        if (in_array($label, self::ONE_SIZE_WORDS, true)) {
            return 'one-size';
        }

        return preg_replace('/\s+/u', '-', $label);
    }

    private static function sizeLabel(AttributeValue $value): string
    {
        return $value->code === 'one-size' ? 'مقاس واحد' : (string) $value->value;
    }

    /**
     * Switches on the chosen values for the product and off the others.
     *
     * @param  \Illuminate\Support\Collection<int, AttributeValue>  $chosen
     * @return \Illuminate\Support\Collection<int, ProductAttributeValue> the active rows, keyed by attribute value id
     */
    private static function activate(ProductAttribute $axis, $chosen)
    {
        $chosenIds = $chosen->pluck('id');

        ProductAttributeValue::where('product_attribute_id', $axis->id)
            ->whereNotIn('attribute_value_id', $chosenIds)
            ->update(['is_active' => false]);

        return $chosen->mapWithKeys(fn (AttributeValue $value) => [
            $value->id => ProductAttributeValue::updateOrCreate(
                ['product_attribute_id' => $axis->id, 'attribute_value_id' => $value->id],
                ['is_active' => true],
            ),
        ]);
    }

    /**
     * One variant for every chosen colour and size; existing ones are switched on, the ones that use a colour or size
     * that was taken off are switched off, missing ones are created.
     *
     * @param  \Illuminate\Support\Collection<int, ProductAttributeValue>  $colorPav
     * @param  \Illuminate\Support\Collection<int, ProductAttributeValue>  $sizePav
     */
    private static function syncVariants(Product $product, $colorPav, $sizePav): void
    {
        $allColorIds = ProductAttributeValue::whereIn('product_attribute_id', $colorPav->pluck('product_attribute_id')->unique())->pluck('id')->all();
        $allSizeIds = ProductAttributeValue::whereIn('product_attribute_id', $sizePav->pluck('product_attribute_id')->unique())->pluck('id')->all();

        // The product's existing variants, found by their colour and size.
        $existing = [];
        foreach (Variant::with('values')->where('product_id', $product->id)->get() as $variant) {
            $pavIds = $variant->values->pluck('product_attribute_value_id');
            $color = $pavIds->first(fn ($id) => in_array($id, $allColorIds, true));
            $size = $pavIds->first(fn ($id) => in_array($id, $allSizeIds, true));

            if ($color && $size) {
                $existing[$color.'|'.$size] = $variant;
            }
        }

        $wanted = [];
        foreach ($colorPav as $color) {
            foreach ($sizePav as $size) {
                $key = $color->id.'|'.$size->id;
                $wanted[$key] = true;

                if (isset($existing[$key])) {
                    $existing[$key]->update(['is_active' => true]);

                    continue;
                }

                $variant = Variant::create([
                    'product_id' => $product->id,
                    'sku' => self::uniqueSku($product, $color, $size),
                    'is_active' => true,
                ]);
                VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $color->id]);
                VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $size->id]);
            }
        }

        foreach ($existing as $key => $variant) {
            if (! isset($wanted[$key])) {
                $variant->update(['is_active' => false]);
            }
        }
    }

    /** PRODUCT-COLOR-SIZE in capitals, the pattern the catalog already uses; a number is added when it is taken. */
    private static function uniqueSku(Product $product, ProductAttributeValue $color, ProductAttributeValue $size): string
    {
        $colorCode = $color->attributeValue->code;
        $sizeCode = $size->attributeValue->code;
        $base = Str::upper($product->code.'-'.$colorCode.($sizeCode !== 'one-size' ? '-'.$sizeCode : ''));

        $sku = $base;
        for ($n = 2; Variant::where('sku', $sku)->exists(); $n++) {
            $sku = $base.'-'.$n;
        }

        return $sku;
    }
}
