<?php

use App\Support\DemoData;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/*
 * The same run-once step as 2026_10_22_000000_seed_demo_data_when_too_few_shops, again: that one already ran on the
 * production server before the demo data was switched on there by default (config/demo.php), so it seeded nothing.
 * Seeds only when the site still has fewer approved print shops than DEMO_MIN_SHOPS. A seeding error is logged and
 * does not stop the deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! config('demo.seed_on_deploy') || app()->runningUnitTests()) {
            return;
        }

        try {
            $result = DemoData::seedIfNeeded();
            Log::info('Demo data on deploy', $result);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function down(): void
    {
        // Demo data is removed with `php artisan demo:shops-clean`, not by rolling back.
    }
};
