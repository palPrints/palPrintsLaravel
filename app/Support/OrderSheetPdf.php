<?php

namespace App\Support;

use Mpdf\Mpdf;
use RuntimeException;

/**
 * The two PDFs a print shop gets for an order line (see OrderLineDocuments):
 *  - details(): the specifications, the notes and the print files — text only;
 *  - design(): per print area the print-ready picture on its own and the product with the design on it. Pictures are
 *    drawn with GD, because the PDF writer cannot tint a garment or place one picture on another by itself.
 */
class OrderSheetPdf
{
    private const GARMENT_WIDTH = 700;

    /** @param  array<string, mixed>  $data  what OrderLineDocuments::sheetData() returns */
    public function details(array $data): string
    {
        // A paper order has no garment or print areas: it gets its own, simpler sheet with a card per file.
        $view = empty($data['item']['paper_files']) ? 'printProvider.order-pdf' : 'printProvider.order-pdf-paper';

        return $this->pdf(view($view, $data + ['previewImages' => []])->render(), 'Order '.$data['order']->order_number);
    }

    /**
     * @param  array<string, mixed>  $data  what OrderLineDocuments::sheetData() returns
     * @param  array<string, string>  $designFiles  area name => path of its print-ready picture
     * @param  string|null  $designPicture  the design's own picture, shown when there is no print-ready picture
     */
    public function design(array $data, array $designFiles, ?string $designPicture = null): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('The GD extension is required to build the order PDF.');
        }

        $previews = array_map(function (array $preview) use ($designFiles) {
            $design = isset($designFiles[$preview['name']]) ? $this->load($designFiles[$preview['name']]) : null;
            $garment = $this->garment(public_path($preview['garment_path']), $preview['tint']);

            return [
                'name' => $preview['name'],
                'bare' => $this->dataUri($this->flatten($garment)),
                'with_design' => $design ? $this->dataUri($this->flatten($this->place($garment, $design, $preview['zone']))) : null,
            ];
        }, $data['previews']);

        // Each print-ready picture on its own, at full size on a light board (lossless PNG, it is what gets printed).
        $designs = collect($designFiles)->map(fn (string $path, string $name) => ['name' => $name, 'src' => $this->pngUri($this->load($path))])->values()->all();
        if ($designs === [] && $designPicture) {
            $designs[] = ['name' => 'التصميم', 'src' => $this->pngUri($this->load($designPicture))];
        }

        $html = view('printProvider.order-pdf-design', $data + ['previewImages' => $previews, 'designs' => $designs])->render();

        return $this->pdf($html, 'Design '.$data['order']->order_number);
    }

    private function pdf(string $html, string $title): string
    {
        $directory = storage_path('app/mpdf');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $pdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $directory, 'default_font' => 'dejavusans', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 12]);
        $pdf->SetDirectionality('rtl');
        $pdf->autoScriptToLang = true;
        $pdf->autoLangToFont = true;
        $pdf->SetTitle($title);
        $pdf->WriteHTML($html);

        return $pdf->Output('', 'S');
    }

    private function load(string $path): \GdImage
    {
        $image = @imagecreatefromstring((string) file_get_contents($path));

        if ($image === false) {
            throw new RuntimeException('Cannot read a picture for the order PDF.');
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    /** The garment picture at a working size, multiplied by the colour the customer chose (the shading stays). */
    private function garment(string $path, ?string $tint): \GdImage
    {
        $source = $this->load($path);
        $garment = $this->canvas(self::GARMENT_WIDTH, (int) round(imagesy($source) * self::GARMENT_WIDTH / imagesx($source)));
        imagecopyresampled($garment, $source, 0, 0, 0, 0, imagesx($garment), imagesy($garment), imagesx($source), imagesy($source));

        if ($tint && preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $tint, $m)) {
            [$red, $green, $blue] = [hexdec($m[1]) / 255, hexdec($m[2]) / 255, hexdec($m[3]) / 255];

            for ($y = 0, $height = imagesy($garment); $y < $height; $y++) {
                for ($x = 0, $width = imagesx($garment); $x < $width; $x++) {
                    $color = imagecolorat($garment, $x, $y);
                    imagesetpixel($garment, $x, $y, ($color & 0x7F000000) | ((int) ((($color >> 16) & 0xFF) * $red) << 16) | ((int) ((($color >> 8) & 0xFF) * $green) << 8) | (int) (($color & 0xFF) * $blue));
                }
            }
        }

        return $garment;
    }

    /** The design inside the print zone (percent of the garment picture), kept in proportion. */
    private function place(\GdImage $garment, \GdImage $design, array $zone): \GdImage
    {
        $result = $this->canvas(imagesx($garment), imagesy($garment));
        imagecopy($result, $garment, 0, 0, 0, 0, imagesx($garment), imagesy($garment));

        $boxWidth = imagesx($garment) * (float) ($zone['width'] ?? 100) / 100;
        $boxHeight = imagesy($garment) * (float) ($zone['height'] ?? 100) / 100;
        $scale = min($boxWidth / imagesx($design), $boxHeight / imagesy($design));
        $width = max(1, (int) round(imagesx($design) * $scale));
        $height = max(1, (int) round(imagesy($design) * $scale));
        $left = (int) round(imagesx($garment) * (float) ($zone['left'] ?? 0) / 100 + ($boxWidth - $width) / 2);
        $top = (int) round(imagesy($garment) * (float) ($zone['top'] ?? 0) / 100 + ($boxHeight - $height) / 2);

        imagealphablending($result, true);
        imagecopyresampled($result, $design, $left, $top, 0, 0, $width, $height, imagesx($design), imagesy($design));
        imagealphablending($result, false);

        return $result;
    }

    /** A picture on a plain background (the PDF has no use for transparency). */
    private function flatten(\GdImage $image, string $background = 'f1f4f9'): \GdImage
    {
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, (int) hexdec($background));
        imagealphablending($flat, true);
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return $flat;
    }

    private function canvas(int $width, int $height): \GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        return $canvas;
    }

    /** A picture as lossless PNG with its transparency kept (the page puts a light board behind it, so white still shows). */
    private function pngUri(\GdImage $image): string
    {
        ob_start();
        imagepng($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    private function dataUri(\GdImage $image): string
    {
        ob_start();
        imagejpeg($image, null, 88);

        return 'data:image/jpeg;base64,'.base64_encode((string) ob_get_clean());
    }
}
