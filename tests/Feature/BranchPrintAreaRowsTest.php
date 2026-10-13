<?php

use App\Models\BranchPrintArea;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BackfillBranchPrintAreasSeeder;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $owner = User::factory()->create();
    $owner->assignRole('print_provider');
    $provider = $owner->printProvider()->create(['company_name' => 'مطبعة الأمل', 'approval_status' => 'draft']);
    $this->branch = $provider->primaryBranch();

    $category = Category::create(['name' => 'ملابس', 'slug' => 'clothes']);
    $this->tshirt = Product::create(['category_id' => $category->id, 'name' => 'تيشيرت', 'code' => 'TSHIRT-CLASSIC', 'is_active' => true]);

    $this->offering = $this->branch->branchProductOfferings()->create([
        'product_id' => $this->tshirt->id, 'base_price' => 0, 'currency' => 'ILS',
        'production_time_min' => 0, 'production_time_max' => 0, 'daily_capacity' => 0, 'is_active' => false,
    ]);
});

test('an offering gets a switched-off row per print area of its product', function () {
    $this->offering->ensurePrintAreas();

    $areas = $this->offering->branchPrintAreas()->get();

    expect($areas->pluck('code')->sort()->values()->all())->toBe(['back', 'front', 'left-sleeve', 'right-sleeve'])
        ->and($areas->where('is_active', true))->toBeEmpty()
        ->and($areas->firstWhere('code', 'front')->max_width_mm)->toBe(210);
});

test('creating the rows again keeps what the shop already ticked', function () {
    $this->offering->ensurePrintAreas();
    $this->offering->branchPrintAreas()->where('code', 'front')->update(['is_active' => true, 'max_width_mm' => 150]);

    $this->offering->ensurePrintAreas();

    expect(BranchPrintArea::where('branch_product_offering_id', $this->offering->id)->count())->toBe(4);

    $front = $this->offering->branchPrintAreas()->where('code', 'front')->first();
    expect($front->is_active)->toBeTrue()->and($front->max_width_mm)->toBe(150);
});

test('the backfill seeder fills offerings that have no rows', function () {
    $this->seed(BackfillBranchPrintAreasSeeder::class);

    expect($this->offering->branchPrintAreas()->count())->toBe(4);
});
