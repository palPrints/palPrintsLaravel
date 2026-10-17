<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Sticker library (categories + sticker images) managed by the admin.
 * Front end only for now: the sticker tables are supplied by the team, and saving is wired to them afterwards.
 */
class StickerController extends Controller
{
    public function index(): View
    {
        return view('admin.stickers');
    }
}
