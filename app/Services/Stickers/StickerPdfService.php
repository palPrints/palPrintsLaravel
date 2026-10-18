<?php

namespace App\Services\Stickers;

use App\Models\OrderItem;
use App\Models\PrintFile;
use App\Models\StickerBundleLine;
use App\Models\StickerLibraryItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StickerPdfService
{
    /**
     * Generates a compact A4 PDF from the saved snapshot. It intentionally has no external package dependency;
     * it can be replaced by Dompdf/mPDF later without changing callers.
     */
    public function generateForOrderItem(OrderItem $orderItem): ?PrintFile
    {
        $options = $orderItem->selected_options ?? [];
        $snapshot = $options['layout_snapshot'] ?? null;

        if (! is_array($snapshot) || empty($snapshot['pages'])) {
            return null;
        }

        $orderItem->loadMissing('order', 'stickerBundleLines.libraryItem', 'stickerBundleLines.printFile');
        $images = $this->imagesFor($orderItem);
        $pdf = $this->build($snapshot, $images);
        $path = 'customer-print-files/orders/'.$orderItem->order_id.'/stickers-'.Str::uuid().'.pdf';
        Storage::disk('local')->put($path, $pdf);

        return PrintFile::create([
            'user_id' => $orderItem->order->user_id,
            'product_id' => $orderItem->product_id,
            'order_item_id' => $orderItem->id,
            'original_name' => 'palprints-stickers-'.$orderItem->order_id.'-'.$orderItem->id.'.pdf',
            'stored_path' => $path,
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => strlen($pdf),
            'page_count' => (int) ($snapshot['page_count'] ?? count($snapshot['pages'])),
            'status' => PrintFile::STATUS_ATTACHED_TO_ORDER,
            'uploaded_at' => now(),
            'attached_to_order_at' => now(),
        ]);
    }

    /** @return array<string, array{data: string, width: int, height: int}> */
    private function imagesFor(OrderItem $orderItem): array
    {
        $images = [];

        foreach ($orderItem->stickerBundleLines as $line) {
            $key = $this->imageKey($line);
            $path = null;
            $disk = null;

            if ($line->source === StickerBundleLine::SOURCE_LIBRARY && $line->libraryItem) {
                $path = $line->libraryItem->image_path;
                $disk = $line->libraryItem->disk;
            } elseif ($line->source === StickerBundleLine::SOURCE_UPLOAD && $line->printFile) {
                $path = $line->printFile->stored_path;
                $disk = $line->printFile->disk ?: 'local';
            }

            if (! $path || isset($images[$key])) {
                continue;
            }

            $image = $this->imageData($disk ?: 'local', $path);
            if ($image) {
                $images[$key] = $image;
                if ($line->upload_key) {
                    $images['upload:'.$line->upload_key] = $image;
                }
                if ($line->client_id) {
                    $images['upload:'.$line->client_id] = $image;
                }
            }
        }

        return $images;
    }

    private function imageKey(StickerBundleLine $line): string
    {
        return $line->source === StickerBundleLine::SOURCE_LIBRARY
            ? 'library:'.$line->sticker_library_item_id
            : 'upload:'.$line->print_file_id;
    }

    /** @return array{data: string, width: int, height: int}|null */
    private function imageData(string $diskName, string $path): ?array
    {
        $disk = Storage::disk($diskName);
        if (! $disk->exists($path)) {
            return null;
        }

        $bytes = $disk->get($path);
        $info = @getimagesizefromstring($bytes);
        if (! $info) {
            return null;
        }

        if (($info['mime'] ?? '') === 'image/jpeg') {
            return ['data' => $bytes, 'width' => (int) $info[0], 'height' => (int) $info[1]];
        }

        if (($info['mime'] ?? '') === 'image/png' && function_exists('imagecreatefromstring')) {
            $source = @imagecreatefromstring($bytes);
            if (! $source) {
                return null;
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $canvas = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
            imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);
            ob_start();
            imagejpeg($canvas, null, 92);
            $jpg = (string) ob_get_clean();
            imagedestroy($source);
            imagedestroy($canvas);

            return ['data' => $jpg, 'width' => $width, 'height' => $height];
        }

        return null;
    }

    /** @param array<string, mixed> $snapshot @param array<string, array{data: string, width: int, height: int}> $images */
    private function build(array $snapshot, array $images): string
    {
        $objects = [];
        $pagesKids = [];
        $pageWidthPt = $this->mmToPt((float) ($snapshot['page']['width_mm'] ?? 210));
        $pageHeightPt = $this->mmToPt((float) ($snapshot['page']['height_mm'] ?? 297));

        $addObject = function (string $body) use (&$objects): int {
            $objects[] = $body;
            return count($objects);
        };

        $catalogId = $addObject('<< /Type /Catalog /Pages 2 0 R >>');
        $pagesId = $addObject(''); // filled after page objects are known

        foreach ($snapshot['pages'] as $page) {
            $xObjects = [];
            $content = "q\n";
            $lineColor = $this->rgb((string) ($snapshot['page']['cut_line']['color'] ?? '#00A3A3'));
            $lineWidth = $this->mmToPt((float) ($snapshot['page']['cut_line']['width_mm'] ?? 0.25));
            $content .= sprintf("%.3F %.3F %.3F RG\n%.3F w\n", $lineColor[0], $lineColor[1], $lineColor[2], max(0.1, $lineWidth));

            foreach (($page['items'] ?? []) as $item) {
                $x = $this->mmToPt((float) $item['x_mm']);
                $y = $pageHeightPt - $this->mmToPt((float) $item['y_mm']) - $this->mmToPt((float) $item['height_mm']);
                $w = $this->mmToPt((float) $item['width_mm']);
                $h = $this->mmToPt((float) $item['height_mm']);
                $key = ($item['source'] ?? '') === 'library' ? 'library:'.$item['library_item_id'] : 'upload:'.$item['upload_key'];
                $image = $images[$key] ?? null;

                if ($image) {
                    $name = 'Im'.(count($xObjects) + 1);
                    $imageId = $addObject(
                        "<< /Type /XObject /Subtype /Image /Width {$image['width']} /Height {$image['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($image['data'])." >>\nstream\n".$image['data']."\nendstream"
                    );
                    $xObjects[$name] = $imageId;
                    $content .= sprintf("q %.3F 0 0 %.3F %.3F %.3F cm /%s Do Q\n", $w, $h, $x, $y, $name);
                } else {
                    $content .= sprintf("0.96 0.98 1 rg %.3F %.3F %.3F %.3F re f\n", $x, $y, $w, $h);
                }

                $content .= sprintf("%.3F %.3F %.3F %.3F re S\n", $x, $y, $w, $h);
            }

            $content .= "Q\n";
            $contentId = $addObject("<< /Length ".strlen($content)." >>\nstream\n".$content."endstream");
            $resources = '<<';
            if ($xObjects !== []) {
                $resources .= ' /XObject << ';
                foreach ($xObjects as $name => $id) {
                    $resources .= '/'.$name.' '.$id.' 0 R ';
                }
                $resources .= '>>';
            }
            $resources .= ' >>';
            $pageId = $addObject("<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidthPt} {$pageHeightPt}] /Resources {$resources} /Contents {$contentId} 0 R >>");
            $pagesKids[] = $pageId;
        }

        $objects[$pagesId - 1] = '<< /Type /Pages /Kids ['.implode(' ', array_map(fn ($id) => $id.' 0 R', $pagesKids)).'] /Count '.count($pagesKids).' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $body) {
            $offsets[$i + 1] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function mmToPt(float $mm): float
    {
        return round($mm * 72 / 25.4, 3);
    }

    /** @return array{0: float, 1: float, 2: float} */
    private function rgb(string $hex): array
    {
        if (! preg_match('/^#?([0-9a-fA-F]{6})$/', $hex, $matches)) {
            return [0, 0.64, 0.64];
        }

        return [
            hexdec(substr($matches[1], 0, 2)) / 255,
            hexdec(substr($matches[1], 2, 2)) / 255,
            hexdec(substr($matches[1], 4, 2)) / 255,
        ];
    }
}
