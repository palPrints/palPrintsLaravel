<?php

namespace App\Services\Stickers;

use Illuminate\Validation\ValidationException;

class StickerLayoutService
{
    /**
     * @param array<int, array<string, mixed>> $input
     * @param array<string, mixed> $settings
     * @return array<int, array<string, mixed>>
     */
    public function normalizeLines(array $input, array $settings): array
    {
        $sizes = collect($settings['layout']['sizes'])->keyBy('key');

        return collect($input)->values()->map(function (array $line, int $index) use ($sizes) {
            $sizeKey = (string) ($line['size_key'] ?? '5x5');
            $size = $sizes->get($sizeKey);

            if (! $size) {
                throw ValidationException::withMessages(['lines' => 'ط§ط®طھط± ظ…ظ‚ط§ط³ظ‹ط§ ظ…طھط§ط­ظ‹ط§ ظ„ظƒظ„ ظ…ظ„طµظ‚.']);
            }

            $quantity = (int) ($line['quantity'] ?? 1);
            if ($quantity < 1 || $quantity > 999) {
                throw ValidationException::withMessages(['lines' => 'ظƒظ…ظٹط© ط§ظ„ظ…ظ„طµظ‚ ط؛ظٹط± طµط­ظٹط­ط©.']);
            }

            $source = (string) ($line['source'] ?? '');
            if (! in_array($source, ['library', 'upload'], true)) {
                throw ValidationException::withMessages(['lines' => 'ظ…طµط¯ط± ط§ظ„ظ…ظ„طµظ‚ ط؛ظٹط± طµط­ظٹط­.']);
            }

            $cutType = (string) ($line['cut_type'] ?? 'circle');
            if (! in_array($cutType, ['circle', 'contour'], true)) {
                throw ValidationException::withMessages(['lines' => 'ظ†ظˆط¹ ط§ظ„ظ‚طµ ط؛ظٹط± ظ…ط¯ط¹ظˆظ….']);
            }

            return [
                'client_id' => (string) ($line['client_id'] ?? 'line-'.$index),
                'source' => $source,
                'library_item_id' => isset($line['library_item_id']) ? (int) $line['library_item_id'] : null,
                'upload_key' => isset($line['upload_key']) ? (string) $line['upload_key'] : null,
                'image_url' => isset($line['image_url']) ? (string) $line['image_url'] : null,
                'label' => mb_substr(strip_tags((string) ($line['label'] ?? '')), 0, 120),
                'notes' => mb_substr(strip_tags((string) ($line['notes'] ?? '')), 0, 500),
                'quantity' => $quantity,
                'size_key' => $sizeKey,
                'width_mm' => (float) $size['width_mm'],
                'height_mm' => (float) $size['height_mm'],
                'cut_type' => $cutType,
                'image_width_px' => isset($line['image_width_px']) ? (int) $line['image_width_px'] : null,
                'image_height_px' => isset($line['image_height_px']) ? (int) $line['image_height_px'] : null,
                'sort_order' => $index,
            ];
        })->all();
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function arrange(array $lines, array $settings): array
    {
        $layout = $settings['layout'];
        $pageWidth = (float) $layout['page_width_mm'];
        $pageHeight = (float) $layout['page_height_mm'];
        $marginTop = (float) $layout['margin_top_mm'];
        $marginRight = (float) $layout['margin_right_mm'];
        $marginBottom = (float) $layout['margin_bottom_mm'];
        $marginLeft = (float) $layout['margin_left_mm'];
        $gap = (float) $layout['gap_mm'];
        $pages = [[]];
        $pageIndex = 0;
        $x = $pageWidth - $marginRight;
        $y = $marginTop;
        $rowHeight = 0.0;
        $placements = [];

        foreach ($lines as $line) {
            for ($copy = 1; $copy <= (int) $line['quantity']; $copy++) {
                $w = (float) $line['width_mm'];
                $h = (float) $line['height_mm'];

                if ($w > $pageWidth - $marginLeft - $marginRight || $h > $pageHeight - $marginTop - $marginBottom) {
                    throw ValidationException::withMessages(['lines' => 'ظ…ظ‚ط§ط³ ط£ط­ط¯ ط§ظ„ظ…ظ„طµظ‚ط§طھ ط£ظƒط¨ط± ظ…ظ† ظ…ط³ط§ط­ط© A4 ط§ظ„ظ…طھط§ط­ط©.']);
                }

                if ($x - $w < $marginLeft) {
                    $x = $pageWidth - $marginRight;
                    $y += $rowHeight + $gap;
                    $rowHeight = 0.0;
                }

                if ($y + $h > $pageHeight - $marginBottom) {
                    $pageIndex++;
                    $pages[$pageIndex] = [];
                    $x = $pageWidth - $marginRight;
                    $y = $marginTop;
                    $rowHeight = 0.0;
                }

                $placement = [
                    'client_id' => $line['client_id'],
                    'source' => $line['source'],
                    'library_item_id' => $line['library_item_id'],
                    'upload_key' => $line['upload_key'],
                    'label' => $line['label'],
                    'copy' => $copy,
                    'page' => $pageIndex + 1,
                    'x_mm' => round($x - $w, 2),
                    'y_mm' => round($y, 2),
                    'width_mm' => round($w, 2),
                    'height_mm' => round($h, 2),
                    'cut_type' => $line['cut_type'],
                    'image_url' => $line['image_url'],
                ];
                $pages[$pageIndex][] = $placement;
                $placements[] = $placement;
                $x -= $w + $gap;
                $rowHeight = max($rowHeight, $h);
            }
        }

        return [
            'version' => 1,
            'page' => [
                'width_mm' => $pageWidth,
                'height_mm' => $pageHeight,
                'margins_mm' => [
                    'top' => $marginTop,
                    'right' => $marginRight,
                    'bottom' => $marginBottom,
                    'left' => $marginLeft,
                ],
                'gap_mm' => $gap,
                'cut_line' => [
                    'enabled' => (bool) $layout['cut_line_enabled'],
                    'width_mm' => (float) $layout['cut_line_width_mm'],
                    'color' => (string) $layout['cut_line_color'],
                ],
                'bleed_mm' => (float) $layout['bleed_mm'],
                'safe_margin_mm' => (float) $layout['safe_margin_mm'],
                'dpi' => (int) $layout['dpi'],
            ],
            'pages' => array_map(fn (array $items, int $index) => ['number' => $index + 1, 'items' => $items], array_values($pages), array_keys(array_values($pages))),
            'placements' => $placements,
            'page_count' => count($pages),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $settings
     * @return array<int, string>
     */
    public function qualityWarnings(array $lines, array $settings): array
    {
        $dpi = max(72, (int) $settings['layout']['dpi']);

        return collect($lines)->flatMap(function (array $line) use ($dpi) {
            if (! $line['image_width_px'] || ! $line['image_height_px']) {
                return [];
            }

            $neededW = (int) ceil(((float) $line['width_mm'] / 25.4) * $dpi);
            $neededH = (int) ceil(((float) $line['height_mm'] / 25.4) * $dpi);

            if ($line['image_width_px'] < $neededW || $line['image_height_px'] < $neededH) {
                return ['low_resolution:'.$line['client_id']];
            }

            return [];
        })->values()->all();
    }
}
