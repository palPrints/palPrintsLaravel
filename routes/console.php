<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('guests:prune {days=7}', function (int $days) {
    $this->info(\App\Support\GuestShopper::prune($days).' abandoned guest carts removed.');
})->purpose('Delete guest-cart users that have been idle for N days');

\Illuminate\Support\Facades\Schedule::command('guests:prune')->daily();

// Demo shops and orders (see DemoShopsSeeder): `php artisan db:seed --class=DemoShopsSeeder` adds them, this removes exactly those.
Artisan::command('demo:shops-clean', function () {
    $removed = \Database\Seeders\DemoShopsSeeder::clean();
    $this->info('Removed '.$removed['orders'].' demo order(s) and '.$removed['accounts'].' demo account(s) with their shops.');
})->purpose('Remove the demo print shops, customers and orders made by DemoShopsSeeder');

// Print-shop identity/licence files and support attachments used to be saved on the public disk (open to anyone with the link).
// This copies them to the private disk under the same path, checks the copy, and only then removes the public one.
Artisan::command('documents:make-private {--dry-run : List what would be moved without touching any file}', function () {
    $public = \Illuminate\Support\Facades\Storage::disk('public');
    $private = \Illuminate\Support\Facades\Storage::disk('local');
    $dry = (bool) $this->option('dry-run');
    $moved = $failed = 0;

    foreach (['print-provider/documents', 'support-attachments'] as $directory) {
        foreach ($public->allFiles($directory) as $path) {
            if ($dry) {
                $this->line('would move: '.$path);
                $moved++;

                continue;
            }

            if (! $private->exists($path)) {
                $private->put($path, $public->get($path));
            }

            if ($private->size($path) !== $public->size($path)) {
                $this->error('copy does not match, kept the public file: '.$path);
                $failed++;

                continue;
            }

            $public->delete($path);
            $moved++;
        }
    }

    $this->info(($dry ? 'Would move ' : 'Moved ').$moved.' file(s) to the private disk; '.$failed.' failed.');
})->purpose('Move print-shop documents and support attachments from the public disk to the private one');

// Telegram bot updates on a local machine (no public URL needed): long-polls Telegram and handles the shops' taps and
// messages. Production uses the webhook instead (telegram:webhook); Telegram allows only one of the two at a time.
Artisan::command('telegram:poll {--once : Fetch one batch of updates and stop}', function () {
    $client = app(\App\Services\Telegram\TelegramClient::class);
    $handler = app(\App\Services\Telegram\TelegramUpdateHandler::class);

    if (blank(config('telegram.bot_token'))) {
        $this->error('TELEGRAM_BOT_TOKEN is not set in .env.');

        return 1;
    }

    $client->deleteWebhook();
    $this->info('Polling @'.($client->getMe()['username'] ?? '?').' for updates. Ctrl+C to stop.');

    do {
        $offset = (int) \Illuminate\Support\Facades\Cache::get('telegram:poll-offset', 0);

        try {
            $updates = $client->getUpdates($offset, $this->option('once') ? 0 : 25);
        } catch (\Throwable $exception) {
            $this->warn('Telegram unreachable: '.$exception->getMessage());
            sleep(5);

            continue;
        }

        foreach ($updates as $update) {
            $handler->handle($update);
            \Illuminate\Support\Facades\Cache::forever('telegram:poll-offset', (int) $update['update_id'] + 1);
            $this->line('handled update '.$update['update_id'].' ('.(isset($update['callback_query']) ? 'button' : 'message').')');
        }
    } while (! $this->option('once'));

    return 0;
})->purpose('Receive Telegram bot updates by polling (local development)');

Artisan::command('telegram:webhook {url? : https URL of /webhooks/telegram} {--delete : Remove the webhook (to poll instead)}', function () {
    $client = app(\App\Services\Telegram\TelegramClient::class);

    if ($this->option('delete')) {
        $client->deleteWebhook();
        $this->info('Webhook removed.');

        return 0;
    }

    $secret = (string) config('telegram.webhook_secret');
    if ($secret === '') {
        $this->error('Set TELEGRAM_WEBHOOK_SECRET in .env first (letters, digits, _ and -).');

        return 1;
    }

    $url = (string) ($this->argument('url') ?: route('webhooks.telegram'));
    $client->setWebhook($url, $secret);
    $this->info('Webhook set to '.$url);

    return 0;
})->purpose('Point the Telegram bot at this site\'s webhook');
