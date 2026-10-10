<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Real, persisted platform settings — stored as a JSON file on the local
 * disk (no dedicated settings table exists in the current schema yet).
 */
class PlatformSettings
{
    private const PATH = 'platform-settings.json';

    private const DEFAULTS = [
        'general' => [
            'platform_name' => 'PalPrints',
            'admin_email' => 'admin@palprints.ps',
            'contact_phone' => '+970 59 200 0000',
            'currency' => 'ILS',
            'description' => 'منصة فلسطينية للطباعة عند الطلب تربط العملاء بالمصممين والمطابع.',
            'allow_registration' => true,
            'maintenance_mode' => false,
        ],
        'payments' => [
            'jawwal_pay' => true,
            'palpay' => true,
            'bank_of_palestine' => false,
        ],
        'notifications' => [
            'order_updates' => true,
            'approval_results' => true,
            'payments_withdrawals' => true,
            'marketing' => false,
        ],
        'site' => [
            'return_days' => 7,
            'instagram' => '',
            'facebook' => '',
            'tiktok' => '',
            'whatsapp' => '',
        ],
        'pages' => [
            'terms' => '',
            'privacy' => '',
            'returns' => '',
            'shipping' => '',
            'faq' => '',
        ],
        'fees' => [
            'platform_commission' => 12,
            'payment_processing_fee' => 2.5,
            'minimum_withdrawal' => 100,
            'tax_rate' => 16,
            'shipping_cost' => 5,
        ],
    ];

    public static function all(): array
    {
        if (! Storage::exists(self::PATH)) {
            return self::DEFAULTS;
        }

        $stored = json_decode(Storage::get(self::PATH), true);

        return is_array($stored) ? array_replace_recursive(self::DEFAULTS, $stored) : self::DEFAULTS;
    }

    public static function group(string $group): array
    {
        return self::all()[$group] ?? [];
    }

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        return self::group($group)[$key] ?? $default;
    }

    public static function update(string $group, array $values): array
    {
        $settings = self::all();
        $settings[$group] = array_replace($settings[$group] ?? [], $values);

        Storage::put(self::PATH, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $settings[$group];
    }
}
