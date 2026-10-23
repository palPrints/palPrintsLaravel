<?php

/*
 * Demo (mock) data for a fresh server: products, print shops in Gaza and the West Bank, customers, designers and their
 * designs, a sticker library, delivery companies and orders in every state (see Database\Seeders\DemoShopsSeeder).
 * `php artisan migrate` fills it in once when the site has fewer approved print shops than DEMO_MIN_SHOPS. On by default
 * on the production server (so nothing has to be added to its .env), off on a local machine; DEMO_SEED_ON_DEPLOY
 * overrides both, and the tests are never touched. Run it by hand any time with `php artisan demo:seed-if-needed`;
 * remove the demo shops and orders with `php artisan demo:shops-clean`.
 */
return [

    'seed_on_deploy' => (bool) env('DEMO_SEED_ON_DEPLOY', env('APP_ENV') === 'production'),

    'min_shops' => (int) env('DEMO_MIN_SHOPS', 5),

    // The password of the demo shops, customers, designers and delivery companies (demo.shop1@palprints.test, ...).
    'password' => env('DEMO_PASSWORD', 'password'),

];
