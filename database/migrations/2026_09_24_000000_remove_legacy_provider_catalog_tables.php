<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->copyLegacyOfferingsToBranches();

        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('print_capability_variants');
        Schema::dropIfExists('print_capabilities');
        Schema::dropIfExists('print_areas');
        Schema::dropIfExists('provider_offering_variants');
        Schema::dropIfExists('provider_offerings');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('printing_methods');
    }

    public function down(): void
    {
        // Legacy provider tables were intentionally removed after migrating
        // catalog pricing to print_provider_branches and branch_product_offerings.
    }

    private function copyLegacyOfferingsToBranches(): void
    {
        if (! Schema::hasTable('provider_offerings') || ! Schema::hasTable('print_provider_branches') || ! Schema::hasTable('branch_product_offerings')) {
            return;
        }

        $printProviderId = DB::table('print_providers')->value('id');

        if (! $printProviderId) {
            return;
        }

        $gazaBranchId = $this->branchId($printProviderId, 'Gaza Branch', 'Gaza', 'Gaza Strip');
        $westBankBranchId = $this->branchId($printProviderId, 'West Bank Branch', 'Ramallah', 'West Bank');

        DB::table('provider_offerings')
            ->join('providers', 'providers.id', '=', 'provider_offerings.provider_id')
            ->select([
                'providers.name as provider_name',
                'provider_offerings.product_id',
                'provider_offerings.base_price',
                'provider_offerings.currency',
                'provider_offerings.production_time_min',
                'provider_offerings.production_time_max',
                'provider_offerings.daily_capacity',
                'provider_offerings.is_active',
            ])
            ->orderBy('provider_offerings.id')
            ->get()
            ->each(function ($offering) use ($gazaBranchId, $westBankBranchId): void {
                foreach ($this->targetBranchIds((string) $offering->provider_name, $gazaBranchId, $westBankBranchId) as $branchId) {
                    DB::table('branch_product_offerings')->updateOrInsert(
                        ['print_provider_branch_id' => $branchId, 'product_id' => $offering->product_id],
                        [
                            'base_price' => $offering->base_price,
                            'currency' => $offering->currency ?: 'ILS',
                            'production_time_min' => $offering->production_time_min,
                            'production_time_max' => $offering->production_time_max,
                            'daily_capacity' => $offering->daily_capacity,
                            'is_active' => $offering->is_active,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                }
            });
    }

    private function branchId(int $printProviderId, string $name, string $city, string $region): int
    {
        DB::table('print_provider_branches')->updateOrInsert(
            ['print_provider_id' => $printProviderId, 'name' => $name],
            [
                'city' => $city,
                'region' => $region,
                'address' => $city,
                'phone' => null,
                'working_hours' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('print_provider_branches')
            ->where('print_provider_id', $printProviderId)
            ->where('name', $name)
            ->value('id');
    }

    /**
     * @return array<int, int>
     */
    private function targetBranchIds(string $providerName, int $gazaBranchId, int $westBankBranchId): array
    {
        $providerName = strtolower($providerName);

        if (str_contains($providerName, 'gaza')) {
            return [$gazaBranchId];
        }

        if (str_contains($providerName, 'ramallah') || str_contains($providerName, 'west')) {
            return [$westBankBranchId];
        }

        return [$gazaBranchId, $westBankBranchId];
    }
};
