<?php

namespace App\Support;

use Mpdf\Mpdf;

/**
 * The details PDF a print shop gets for an order line (see OrderLineDocuments): the specifications, the notes and the
 * print files, text only. The pictures to print are separate files, each named by its area ("أمامي.png").
 */
class OrderSheetPdf
{
    /** @param  array<string, mixed>  $data  what OrderLineDocuments::sheetData() returns */
    public function details(array $data): string
    {
        // A paper order has no garment or print areas: it gets its own, simpler sheet with a card per file.
        $view = empty($data['item']['paper_files']) ? 'printProvider.order-pdf' : 'printProvider.order-pdf-paper';

        $directory = storage_path('app/mpdf');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $pdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $directory, 'default_font' => 'dejavusans', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 12]);
        $pdf->SetDirectionality('rtl');
        $pdf->autoScriptToLang = true;
        $pdf->autoLangToFont = true;
        $pdf->SetTitle('Order '.$data['order']->order_number);
        $pdf->WriteHTML(view($view, $data)->render());

        return $pdf->Output('', 'S');
    }
}
