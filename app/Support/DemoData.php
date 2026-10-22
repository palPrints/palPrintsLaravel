<?php

namespace App\Support;

use App\Models\PrintProvider;
use App\Models\User;
use Database\Seeders\CatalogDemoSeeder;
use Database\Seeders\DemoShopsSeeder;
use Database\Seeders\FullDemoScenarioSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StickerDemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fills a site that has too few print shops with the demo data (see config/demo.php): the catalogue, the stickers, a
 * full demo scenario (its "Demo Print House" is the shop the demo shops copy their products and prices from), then
 * the demo marketplace: shops in Gaza and the West Bank, customers, designers, designs, stickers and orders in every state.
 *
 * The demo scenario makes an admin account with a known password. On a production site that account is switched off
 * with a random password right after, so the public site never has an admin anyone can sign in to.
 */
class DemoData
{
    public const DEMO_ADMIN_EMAIL = 'demo.admin@palprints.test';

    /** The seeders, in the order they must run. */
    public const SEEDERS = [
        RoleAndPermissionSeeder::class,
        CatalogDemoSeeder::class,
        StickerDemoSeeder::class,
        FullDemoScenarioSeeder::class,
        DemoShopsSeeder::class,
    ];

    /** Approved, active print shops on the site. */
    public static function shopCount(): int
    {
        return PrintProvider::query()->where('approval_status', 'approved')->where('is_active', true)->count();
    }

    /**
     * Seeds when the site has fewer shops than config('demo.min_shops').
     *
     * @return array{seeded: bool, shops_before: int, shops_after: int}
     */
    public static function seedIfNeeded(): array
    {
        $before = self::shopCount();
        if ($before >= (int) config('demo.min_shops', 5)) {
            return ['seeded' => false, 'shops_before' => $before, 'shops_after' => $before];
        }

        foreach (self::SEEDERS as $seeder) {
            Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        if (app()->isProduction()) {
            User::query()->where('email', self::DEMO_ADMIN_EMAIL)
                ->update(['is_active' => false, 'password' => Hash::make(Str::random(48))]);
        }

        return ['seeded' => true, 'shops_before' => $before, 'shops_after' => self::shopCount()];
    }
}
