<?php

namespace App\Http\Controllers;

use App\Models\PrintFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads a customer's print file from the link in the WhatsApp message the admin sends the shop. No login: the link
 * is signed and expires (see ShopChatLink), and only works for a file that belongs to a placed order.
 */
class SharedPrintFileController extends Controller
{
    public function __invoke(PrintFile $printFile): StreamedResponse
    {
        abort_if($printFile->order_item_id === null || in_array($printFile->status, [PrintFile::STATUS_DELETED, PrintFile::STATUS_CANCELLED], true), 404);

        $disk = Storage::disk($printFile->disk ?: 'local');
        abort_unless($disk->exists($printFile->stored_path), 404);

        return $disk->download($printFile->stored_path, $printFile->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
