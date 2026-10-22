<?php

use App\Support\DemoData;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/*
 * Runs once, with the deploy's `php artisan migrate`: when DEMO_SEED_ON_DEPLOY=true and the site has fewer approved print
 * shops than DEMO_MIN_SHOPS, the demo data is seeded (see App\Support\DemoData). Off by default and never in the tests.
 * A seeding error is logged and does not stop the deploy.
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
