<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Services\Orders\OrderLineDocuments;
use Illuminate\Http\Response;

/**
 * The details / design PDF of one order line, from the signed, expiring link in the WhatsApp message the admin sends the
 * shop (see ShopChatLink). No login; only for a line of an order the admin has approved.
 */
class SharedOrderSheetController extends Controller
{
    public function __invoke(OrderItem $item, string $kind, OrderLineDocuments $documents): Response
    {
        $order = $item->order;
        abort_if(! $order || in_array($order->payment_status, ['pending', 'pending_review', 'failed'], true), 404);

        $item->loadMissing(['product', 'design', 'printFiles']);
        $pdf = $kind === 'design' ? $documents->designPdf($order, $item) : $documents->detailsPdf($order, $item);
        abort_if($pdf === null, 404);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$documents->fileName($order, $item, $kind).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
