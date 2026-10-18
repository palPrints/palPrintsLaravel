<?php

namespace Database\Seeders;

use App\Models\StickerCategory;
use App\Services\Stickers\StickerSettingsService;
use Illuminate\Database\Seeder;

class StickerDemoSeeder extends Seeder
{
    public function run(): void
    {
        app(StickerSettingsService::class)->put(app(StickerSettingsService::class)->get());

        foreach ([
            ['name' => 'هندسة', 'slug' => 'engineering', 'sort_order' => 10],
            ['name' => 'طب', 'slug' => 'medicine', 'sort_order' => 20],
            ['name' => 'عشوائيات', 'slug' => 'random', 'sort_order' => 30],
        ] as $category) {
            StickerCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'is_active' => true, 'sort_order' => $category['sort_order']],
            );
        }
    }
}
