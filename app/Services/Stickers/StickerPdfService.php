<?php

namespace App\Services\Stickers;

use App\Models\OrderItem;
use App\Models\PrintFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;

class StickerPdfService
{
    public function generateForOrderItem(OrderItem $orderItem): PrintFile
    {
        // Serialize regeneration so retries reuse one final file per immutable order item.
        return DB::transaction(function () use ($orderItem) {
            $item = OrderItem::query()->lockForUpdate()->findOrFail($orderItem->id);
            $item->load('order', 'stickerBundleLines.libraryItem', 'stickerBundleLines.printFile');
            if ($item->order->payment_status !== 'paid') {
                throw new RuntimeException('Sticker PDF requires an approved payment.');
            }
            $path = "customer-print-files/orders/{$item->order_id}/stickers-{$item->id}-final.pdf";
            $existing = $item->printFiles()->where('stored_path', $path)->first();
            if ($existing && Storage::disk('local')->exists($path)) {
                return $existing;
            }
            $snapshot = $item->selected_options['layout_snapshot'] ?? null;
            if (! is_array($snapshot) || empty($snapshot['pages'])) {
                throw new RuntimeException('Missing sticker layout snapshot.');
            }
            $bytes = $this->render($item, $snapshot);
            if (! Storage::disk('local')->put($path, $bytes)) {
                throw new RuntimeException('Could not save sticker PDF.');
            }

            return PrintFile::updateOrCreate(['order_item_id' => $item->id, 'stored_path' => $path], [
                'user_id' => $item->order->user_id, 'product_id' => $item->product_id,
                'original_name' => "palprints-stickers-{$item->order_id}-{$item->id}.pdf",
                'disk' => 'local', 'mime_type' => 'application/pdf', 'extension' => 'pdf',
                'file_size' => strlen($bytes), 'page_count' => count($snapshot['pages']),
                'status' => PrintFile::STATUS_ATTACHED_TO_ORDER,
                'uploaded_at' => now(), 'attached_to_order_at' => now(),
            ]);
        });
    }

    private function render(OrderItem $item, array $snapshot): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Enable PHP GD before generating sticker PDFs.');
        }
        $images = [];
        foreach ($item->stickerBundleLines as $line) {
            $asset = $line->source === 'library' ? $line->libraryItem : $line->printFile;
            $path = $line->source === 'library' ? $asset?->image_path : $asset?->stored_path;
            $disk = Storage::disk($asset?->disk ?: 'local');
            if (! $path || ! $disk->exists($path)) {
                throw new RuntimeException('A sticker source image is missing.');
            }
            $bytes = $disk->get($path);
            $info = @getimagesizefromstring($bytes);
            if (! $info || ! in_array($info['mime'], ['image/png', 'image/jpeg'], true)) {
                throw new RuntimeException('Invalid sticker image.');
            }
            $data = ['bytes' => $bytes, 'width' => $info[0], 'height' => $info[1]];
            $images['library:'.$line->sticker_library_item_id] = $data;
            foreach ([$line->upload_key, $line->client_id, $line->print_file_id] as $key) {
                if ($key !== null) {
                    $images['upload:'.$key] = $data;
                }
            }
        }
        $pdf = new Mpdf(['format' => 'A4', 'margin_left' => 0, 'margin_right' => 0,
            'margin_top' => 0, 'margin_bottom' => 0, 'tempDir' => storage_path('app/mpdf')]);
        $pdf->SetTitle('PalPrints stickers - print at actual size (100%)');
        $pdf->SetAutoPageBreak(false);
        $pdf->showImageErrors = true;
        $lineStyle = $snapshot['page']['cut_line'] ?? [];
        $hex = ltrim($lineStyle['color'] ?? '#888888', '#');
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new RuntimeException('Invalid cut line color.');
        }
        $pdf->SetDrawColor(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
        $pdf->SetLineWidth(max(0.05, (float) ($lineStyle['width_mm'] ?? 0.2)));
        $count = 0;
        foreach ($snapshot['pages'] as $page) {
            $pdf->AddPage();
            foreach ($page['items'] as $placement) {
                $key = ($placement['source'] ?? '') === 'library' ? 'library:'.$placement['library_item_id'] : 'upload:'.($placement['upload_key'] ?? $placement['client_id']);
                $image = $images[$key] ?? null;
                if (! $image) {
                    throw new RuntimeException('A layout image could not be resolved.');
                }
                $x = (float) $placement['x_mm'];
                $y = (float) $placement['y_mm'];
                $w = (float) $placement['width_mm'];
                $h = (float) $placement['height_mm'];
                if ($w <= 0 || $h <= 0 || $x < 0 || $y < 0 || $x + $w > 210.01 || $y + $h > 297.01) {
                    throw new RuntimeException('Sticker lies outside A4.');
                }
                // Match the customer's contain preview: preserve aspect ratio, never stretch artwork.
                $scale = min($w / $image['width'], $h / $image['height']);
                $iw = $image['width'] * $scale;
                $ih = $image['height'] * $scale;
                $pdf->imageVars['sticker'.$count] = $image['bytes'];
                $pdf->Image('var:sticker'.$count, $x + ($w - $iw) / 2, $y + ($h - $ih) / 2, $iw, $ih);
                $count++;
                if ($lineStyle['enabled'] ?? true) {
                    if (($placement['cut_type'] ?? '') === 'circle') {
                        $pdf->Ellipse($x + $w / 2, $y + $h / 2, $w / 2, $h / 2, 'D');
                    } else {
                        // Manual contour cutting follows the artwork edge; box marks show the allocated size.
                        $pdf->Rect($x, $y, $w, $h, 'D');
                    }
                }
            }
        }
        if ($count !== (int) $item->stickerBundleLines->sum('quantity')) {
            throw new RuntimeException('Layout quantity differs from ordered quantity.');
        }

        return $pdf->Output('', Destination::STRING_RETURN);
    }
}
