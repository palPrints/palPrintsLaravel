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
