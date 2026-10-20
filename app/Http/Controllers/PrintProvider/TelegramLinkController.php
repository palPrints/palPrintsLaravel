<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Connects the shop's Telegram to the bot (through a one-time t.me link) and disconnects it. */
class TelegramLinkController extends Controller
{
    public function link(Request $request): RedirectResponse
    {
        $provider = $request->user()->printProvider;
        abort_if($provider === null || ! config('telegram.enabled') || blank(config('telegram.bot_username')), 404);

        $token = TelegramUpdateHandler::issueLinkToken($provider);

        return redirect()->away('https://t.me/'.ltrim((string) config('telegram.bot_username'), '@').'?start='.$token);
    }

    public function unlink(Request $request): RedirectResponse
    {
        $request->user()->printProvider?->update(['telegram_chat_id' => null, 'telegram_username' => null, 'telegram_linked_at' => null]);

        return redirect()->route('print-provider.profile')->with('telegram_status', 'تم إلغاء ربط تيليجرام. لن تصلك الطلبات هناك بعد الآن.');
    }
}
