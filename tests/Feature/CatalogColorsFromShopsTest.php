<?php

use App\Models\BranchOfferingVariant;
use App\Models\Product;
use App\Models\User;
use App\Support\CatalogProductData;
use Database\Seeders\CatalogDemoSeeder;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    // The demo seeder only builds a shop's branches when a print shop already exists.
    $owner = User::factory()->create();
    $owner->assignRole('print_provider');
    $this->provider = $owner->printProvider()->create(['company_name' => 'مطبعة', 'approval_status' => 'approved', 'is_active' => true]);
    $this->seed(CatalogDemoSeeder::class);

    $this->tshirt = fn () => Product::where('code', 'TSHIRT-CLASSIC')->firstOrFail()->fresh();
    $colorIds = fn (array $options) => collect($options['colors'])->pluck('id')->sort()->values()->all();
    $this->colors = fn () => $colorIds(CatalogProductData::catalogOptions(($this->tshirt)()));
    $this->sizes = fn () => collect(CatalogProductData::catalogOptions(($this->tshirt)())['sizes'])->pluck('id')->sort()->values()->all();

    /** Marks every variant of the t-shirt that has this colour/size code as not offered by any shop. */
    $this->withdraw = function (string $axis, string $code) {
        $variantIds = ($this->tshirt)()->variants()
            ->whereHas('values.productAttributeValue.attributeValue', fn ($q) => $q->where('code', 'like', '%'.$code))
            ->whereHas('values.productAttributeValue.productAttribute.attribute', fn ($q) => $q->where('code', $axis))
            ->pluck('id');
        BranchOfferingVariant::whereIn('variant_id', $variantIds)->update(['is_available' => false]);
    };
});

test('every colour and size of the product shows while the shops offer them all', function () {
    expect(($this->colors)())->toContain('white', 'black', 'navy')
        ->and(($this->sizes)())->toContain('s', 'm', 'l', 'xl');
});

test('a colour no shop offers is not shown to the customer', function () {
    ($this->withdraw)('color', 'navy');

    expect(($this->colors)())->not->toContain('navy')->toContain('white', 'black');
});

test('a size no shop offers is not shown to the customer', function () {
    ($this->withdraw)('size', 'xl');

    expect(($this->sizes)())->not->toContain('xl')->toContain('s', 'm', 'l');
});

test('the designer and studio catalog is filtered the same way', function () {
    ($this->withdraw)('color', 'navy');

    $product = collect(CatalogProductData::forDesigner()['products'])->firstWhere('code', 'TSHIRT-CLASSIC');

    expect(collect($product['colors'])->pluck('id')->all())->not->toContain('navy')->toContain('black');
});

test('a shop that is not approved does not count as offering a colour', function () {
    ($this->withdraw)('color', 'navy');
    // Only an unapproved shop would still offer navy.
    $this->provider->update(['approval_status' => 'submitted']);

    // No approved shop offers the product at all: it keeps showing what it has, nothing is hidden by a shop that cannot print.
    expect(($this->colors)())->toContain('white', 'black', 'navy');
});

test('a colour is shown when only one of several shops offers it', function () {
    ($this->withdraw)('color', 'navy');
    $branch = $this->provider->branches()->orderBy('id')->first();
    $offering = $branch->branchProductOfferings()->where('product_id', ($this->tshirt)()->id)->firstOrFail();
    $navy = ($this->tshirt)()->variants()->where('sku', 'like', '%-NAVY-%')->pluck('id');

    BranchOfferingVariant::where('branch_product_offering_id', $offering->id)->whereIn('variant_id', $navy)->update(['is_available' => true]);

    expect(($this->colors)())->toContain('navy');
});
