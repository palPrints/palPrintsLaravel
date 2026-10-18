<?php

/*
 * Telegram bot that sends a print shop its new orders (details, the print files attached, Accept / Reject buttons).
 * Create the bot with @BotFather, then set TELEGRAM_BOT_TOKEN and TELEGRAM_BOT_USERNAME. With TELEGRAM_ENABLED=false
 * nothing is sent and the shop links section is hidden.
 *
 * Updates (taps, messages) arrive by webhook in production (php artisan telegram:webhook https://domain/webhooks/telegram)
 * or by polling on a local machine (php artisan telegram:poll), which needs no public URL.
 */
return [

    'enabled' => (bool) env('TELEGRAM_ENABLED', false),

    'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),

    // From @BotFather. Never logged.
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    // The bot's @username without the @, used for the t.me link the shop opens to connect.
    'bot_username' => env('TELEGRAM_BOT_USERNAME'),

    // Sent back by Telegram in X-Telegram-Bot-Api-Secret-Token on every webhook call (letters, digits, _ and -).
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    // How long the "connect Telegram" link from the profile keeps working.
    'link_minutes' => 60,

    // How long a shop has to type its rejection reason after tapping "reject".
    'reject_reason_minutes' => 30,

    // Bots may upload files up to 50 MB; a bigger file is sent as a download link instead.
    'max_upload_bytes' => 50 * 1024 * 1024,

];
