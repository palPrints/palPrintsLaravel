<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The printing methods (DTF, DTG, embroidery...) a customer can choose between for a product: the ones that at least one
 * active, approved print shop really offers on it. A product with a single method has nothing to choose, so it is left out.
 */
class PrintingMethodOptions
{
    /**
     * @return array<string, array<int, array{id: string, name: string}>> product code (upper case) => methods, only products with 2+
     */
    public static function forPreview(): array
    {
        return self::rows()
            ->groupBy('product_code')
            ->map(fn (Collection $rows) => $rows->unique('code')->map(fn ($row) => ['id' => $row->code, 'name' => $row->name])->values()->all())
            ->filter(fn (array $methods) => count($methods) > 1)
            ->all();
    }

    /** The codes the customer may pick for this product (empty when there is nothing to choose). */
    public static function codesFor(string $productCode): array
    {
        return array_column(self::forPreview()[strtoupper($productCode)] ?? [], 'id');
    }

    /** @return Collection<int, object{product_code: string, code: string, name: string}> */
    private static function rows(): Collection
    {
        if (! Schema::hasTable('printing_methods') || ! Schema::hasTable('branch_print_capabilities')) {
            return collect();
        }

        return DB::table('branch_print_capabilities as capability')
            ->join('printing_methods as method', 'method.id', '=', 'capability.printing_method_id')
            ->join('branch_print_areas as area', 'area.id', '=', 'capability.branch_print_area_id')
            ->join('branch_product_offerings as offering', 'offering.id', '=', 'area.branch_product_offering_id')
            ->join('products', 'products.id', '=', 'offering.product_id')
            ->join('print_provider_branches as branch', 'branch.id', '=', 'offering.print_provider_branch_id')
            ->join('print_providers as provider', 'provider.id', '=', 'branch.print_provider_id')
            ->where('capability.is_active', true)
            ->where('method.is_active', true)
            ->where('area.is_active', true)
            ->where('offering.is_active', true)
            ->where('offering.base_price', '>', 0)
            ->where('branch.is_active', true)
            ->where('provider.approval_status', 'approved')
            ->where('provider.is_active', true)
            ->orderBy('method.id')
            ->get(['products.code as product_code', 'method.code', 'method.name'])
            ->map(function ($row) {
                $row->product_code = strtoupper((string) $row->product_code);

                return $row;
            });
    }
}
