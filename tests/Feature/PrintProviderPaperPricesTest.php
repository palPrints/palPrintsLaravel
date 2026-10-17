<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\BranchPricingRule;
use App\Models\BranchPrintArea;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantValue;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $category = Category::create(['name' => 'ورق', 'slug' => 'paper']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'طباعة الأوراق', 'code' => 'PAPER-PRINT', 'is_active' => true]);

    $color = Attribute::create(['name' => 'اللون', 'code' => 'color', 'data_type' => 'text', 'is_active' => true]);
    $size = Attribute::create(['name' => 'المقاس', 'code' => 'size', 'data_type' => 'text', 'is_active' => true]);
    $white = AttributeValue::create(['attribute_id' => $color->id, 'value' => 'أبيض', 'code' => 'white', 'sort_order' => 1]);

    $colorAxis = ProductAttribute::create(['product_id' => $this->product->id, 'attribute_id' => $color->id, 'is_variant_axis' => true, 'is_required' => true, 'sort_order' => 1]);
    $sizeAxis = ProductAttribute::create(['product_id' => $this->product->id, 'attribute_id' => $size->id, 'is_variant_axis' => true, 'is_required' => true, 'sort_order' => 2]);
    $whiteValue = ProductAttributeValue::create(['product_attribute_id' => $colorAxis->id, 'attribute_value_id' => $white->id, 'is_active' => true]);

    foreach (['a4' => 'A4', 'a5' => 'A5'] as $code => $label) {
        $value = AttributeValue::create(['attribute_id' => $size->id, 'value' => $label, 'code' => $code, 'sort_order' => 1]);
        $variant = Variant::create(['product_id' => $this->product->id, 'sku' => 'PP-WHITE-'.$label, 'is_active' => true]);
        $sizeValue = ProductAttributeValue::create(['product_attribute_id' => $sizeAxis->id, 'attribute_value_id' => $value->id, 'is_active' => true]);
        VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $whiteValue->id]);
        VariantValue::create(['variant_id' => $variant->id, 'product_attribute_value_id' => $sizeValue->id]);
    }

    $this->owner = User::factory()->create(['is_active' => true]);
    $this->owner->assignRole('print_provider');
    $this->owner->printProvider()->create(['company_name' => 'مطبعة النور', 'approval_status' => 'approved', 'is_active' => true]);

    // Paper has no print areas or printing methods: only the usual settings and what the shop sells.
    $this->save = fn (array $paper, array $sizes = ['a4', 'a5']) => $this->actingAs($this->owner)->putJson(route('print-provider.services.save', $this->product), $paper + [
        'price' => 1, 'days' => 1, 'capacity' => 50, 'colors' => ['white'], 'sizes' => $sizes,
    ]);

    // A shop prices in packs: "every 100 sheets cost 20", "every 100 pages cost 9".
    $this->full = [
        'paper_types' => ['standard', 'thick'],
        'paper_ink' => [
            'standard' => ['bw' => ['count' => 100, 'price' => 9], 'color' => ['count' => 100, 'price' => 42]],
            'thick' => ['bw' => ['count' => 100, 'price' => 12], 'color' => ['count' => 100, 'price' => 50]],
        ],
        'paper_bindings' => ['spiral' => 8, 'sideStaple' => 1.5],
    ];
});

/** The prices that are valid now: "rule type|variant sku" => [pack price, pack size] (offering-level rules have no variant). */
function validPaperPrices(): array
{
    return BranchPricingRule::where('pricing_type', 'like', 'paper_%')->where('is_active', true)->whereNull('valid_until')->get()
        ->mapWithKeys(fn ($rule) => [$rule->pricing_type.'|'.($rule->branchOfferingVariant?->variant?->sku ?? '-') => [(float) $rule->amount, (int) $rule->min_quantity]])->all();
}

test('the paper product saves, for every paper type, the price of printing in each colour, and the bindings, with no blank-paper price, print areas or methods', function () {
    $response = ($this->save)($this->full)->assertOk();

    expect(validPaperPrices())->toEqualCanonicalizing([
        'paper_ink_standard_bw|-' => [9.0, 100], 'paper_ink_standard_color|-' => [42.0, 100],
        'paper_ink_thick_bw|-' => [12.0, 100], 'paper_ink_thick_color|-' => [50.0, 100],
        'paper_binding_spiral|-' => [8.0, 1], 'paper_binding_sideStaple|-' => [1.5, 1],
    ]);

    // what the dialog shows back
    $types = collect($response->json('product.paper.types'))->keyBy('key');
    $bindings = collect($response->json('product.paper.bindings'))->keyBy('key');
    expect($types['standard']['enabled'])->toBeTrue()
        ->and($types['thick']['ink']['color'])->toMatchArray(['count' => 100, 'amount' => 50.0])
        ->and($types['coated']['enabled'])->toBeFalse()
        ->and($bindings['glue']['enabled'])->toBeFalse()
        ->and($bindings['spiral']['enabled'])->toBeTrue();

    // one sheet / one page is the pack price divided by the pack size
    $offering = \App\Models\BranchProductOffering::firstOrFail();
    $list = app(\App\Services\PaperPricing::class)->priceList($offering);
    expect($list['ink']['thick']['color'])->toBe(0.5)
        ->and($list['ink']['standard']['bw'])->toBe(0.09)
        ->and($list['bindings']['spiral'])->toBe(8.0);

    // no area or method rows were made for the paper
    expect(BranchPrintArea::count())->toBe(0);
});

test('a new price or a new pack keeps the old one on record, an unchanged price is left alone and anything switched off stops being sold', function () {
    ($this->save)($this->full)->assertOk();

    // tomorrow: standard A4 paper goes up; standard black and white is now "10 for 0.90" (same price per page, a new pack);
    // thick paper, standard colour printing and the staple binding are switched off
    ($this->save)([
        'paper_types' => ['standard'],
        'paper_ink' => ['standard' => ['bw' => ['count' => 10, 'price' => 0.9]]],
        'paper_bindings' => ['spiral' => 8],
    ])->assertOk();

    expect(validPaperPrices())->toEqualCanonicalizing([
        'paper_ink_standard_bw|-' => [0.9, 10], 'paper_binding_spiral|-' => [8.0, 1],
    ]);

    $history = BranchPricingRule::where('pricing_type', 'paper_ink_standard_bw')->orderBy('id')->get();
    expect($history)->toHaveCount(2)
        ->and((float) $history[0]->amount)->toBe(9.0)->and($history[0]->is_active)->toBeFalse()->and($history[0]->valid_until)->not->toBeNull()
        ->and((float) $history[1]->amount)->toBe(0.9);

    // the blank paper has no price at all
    expect(BranchPricingRule::where('pricing_type', 'like', 'paper_sheet_%')->where('is_active', true)->count())->toBe(0);

    // the spiral price did not change, so it is still its first rule
    expect(BranchPricingRule::where('pricing_type', 'paper_binding_spiral')->count())->toBe(1);
});

test('every ticked price needs a count and a price, with sense, and every paper type that is on needs a way of printing on it', function () {
    $good = $this->full;
    $with = fn (array $changes) => array_replace_recursive($good, $changes);

    ($this->save)($with(['paper_ink' => ['thick' => ['color' => ['count' => null]]]]))->assertUnprocessable()->assertJsonValidationErrors('paper_ink.thick.color.count');
    ($this->save)(['paper_types' => [], 'paper_ink' => []])->assertUnprocessable()->assertJsonValidationErrors('paper_types');

    // thick paper is on but nothing is said about printing on it
    $noInk = $good;
    $noInk['paper_ink'] = ['standard' => $good['paper_ink']['standard']];
    ($this->save)($noInk)->assertUnprocessable()->assertJsonValidationErrors('paper_ink.thick');

    ($this->save)([])->assertUnprocessable()->assertJsonValidationErrors('paper_types');
    expect(validPaperPrices())->toBe([]);
});

test('a size the shop does not sell needs nothing, and no blank-paper price is kept', function () {
    ($this->save)($this->full)->assertOk();

    ($this->save)([
        'paper_types' => ['standard'],
        'paper_ink' => ['standard' => ['bw' => ['count' => 100, 'price' => 9]]],
    ], ['a4'])->assertOk();

    expect(validPaperPrices())->toEqualCanonicalizing(['paper_ink_standard_bw|-' => [9.0, 100]]);
});

