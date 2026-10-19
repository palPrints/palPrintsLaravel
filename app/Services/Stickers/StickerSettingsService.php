<?php

namespace App\Services\Stickers;

use App\Models\StickerSetting;

class StickerSettingsService
{
    public const KEY = 'palprints_stickers';

    /** @return array<string, mixed> */
    public function get(): array
    {
        $saved = StickerSetting::query()->where('key', self::KEY)->first()?->value;

        return $this->merge(is_array($saved) ? $saved : []);
    }

    /** @param array<string, mixed> $value */
    public function put(array $value): array
    {
        $settings = $this->merge($value);

        StickerSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => $settings],
        );

        return $settings;
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'pricing' => [
                'min_quantity' => 20,
                'warning_quantity' => 300,
                'base_quantity' => 20,
                'base_price' => 10,
                'extra_unit_price' => 1,
                'contour_group_size' => 3,
                'contour_group_price' => 1,
            ],
            'upload' => [
                'max_files' => 20,
                'max_file_mb' => 10,
                'max_total_mb' => 50,
                'allowed_mimes' => ['image/png', 'image/jpeg'],
            ],
            'layout' => [
                'page_width_mm' => 210,
                'page_height_mm' => 297,
                'margin_top_mm' => 10,
                'margin_right_mm' => 10,
                'margin_bottom_mm' => 10,
                'margin_left_mm' => 10,
                'gap_mm' => 3,
                'cut_line_enabled' => true,
                'cut_line_width_mm' => 0.25,
                'cut_line_color' => '#00A3A3',
                'bleed_mm' => 1,
                'safe_margin_mm' => 1,
                'dpi' => 300,
                'sizes' => [
                    ['key' => '3x3', 'label' => '3 x 3 cm', 'width_mm' => 30, 'height_mm' => 30],
                    ['key' => '5x5', 'label' => '5 x 5 cm', 'width_mm' => 50, 'height_mm' => 50],
                    ['key' => '7x7', 'label' => '7 x 7 cm', 'width_mm' => 70, 'height_mm' => 70],
                    ['key' => '10x10', 'label' => '10 x 10 cm', 'width_mm' => 100, 'height_mm' => 100],
                ],
            ],
            'pdf' => [
                'enabled' => true,
                'final_after_payment' => true,
            ],
        ];
    }

    private function merge(array $value): array
    {
        $settings = array_replace_recursive($this->defaults(), $value);
        if (isset($value['layout']['sizes'])) {
            $settings['layout']['sizes'] = array_values($value['layout']['sizes']);
        }
        return $settings;
    }
}
