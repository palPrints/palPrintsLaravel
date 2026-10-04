<?php

namespace Database\Seeders;

use App\Models\BranchProductOffering;
use Illuminate\Database\Seeder;

/**
 * Gives offerings that already exist their print-area rows (switched off until the shop ticks them).
 * Existing rows are never touched, so it can be run again.
 */
class BackfillBranchPrintAreasSeeder extends Seeder
{
    public function run(): void
    {
        BranchProductOffering::query()->with('product')->each(fn (BranchProductOffering $offering) => $offering->ensurePrintAreas());
    }
}
