<?php

namespace App\Support;

use Mpdf\Mpdf;
use RuntimeException;

/**
 * The one file the print shop gets for an order line: the specifications, the product with and without the design,
 * the print-ready picture of every area and where each element sits. Pictures are drawn with GD, because the PDF
 * writer cannot tint a garment or place one picture on another by itself.
 */
class OrderSheetPdf
{
    private const GARMENT_WIDTH = 700;

    private const DESIGN_MAX_SIDE = 1800;

    /**
     * @param  array<string, mixed>  $data  what OrdersController::sheetData() returns
     * @param  array<string, string>  $designFiles  area name => path of its print-ready picture
     */
    public function render(array $data, array $designFiles): string
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

        $designs = [];
        foreach ($designFiles as $name => $path) {
            $image = $this->load($path);
            $designs[] = ['name' => $name, 'src' => $this->dataUri($this->flatten($this->shrink($image, self::DESIGN_MAX_SIDE), 'e9edf3'))];
        }

        $html = view('printProvider.order-pdf', $data + ['previewImages' => $previews, 'designImages' => $designs])->render();

        $directory = storage_path('app/mpdf');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $pdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $directory, 'default_font' => 'dejavusans', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 12]);
        $pdf->SetDirectionality('rtl');
        $pdf->autoScriptToLang = true;
        $pdf->autoLangToFont = true;
        $pdf->SetTitle('Order '.$data['order']->order_number);
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

    private function shrink(\GdImage $image, int $maxSide): \GdImage
    {
        $scale = min(1, $maxSide / max(imagesx($image), imagesy($image)));

        if ($scale === 1) {
            return $image;
        }

        $small = $this->canvas(max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)));
        imagecopyresampled($small, $image, 0, 0, 0, 0, imagesx($small), imagesy($small), imagesx($image), imagesy($image));

        return $small;
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

    private function dataUri(\GdImage $image): string
    {
        ob_start();
        imagejpeg($image, null, 88);

        return 'data:image/jpeg;base64,'.base64_encode((string) ob_get_clean());
    }
}
