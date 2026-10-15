<?php

namespace App\Services\WhatsApp;

/**
 * Turns a phone number as people type it (0599 123 456, +970-599-123456, 00972599123456) into the international digits
 * WhatsApp uses as "to" and "wa_id" (970599123456). A local number gets the configured default country code.
 */
class PhoneNumber
{
    public static function normalize(?string $number): ?string
    {
        $number = trim((string) $number);
        if ($number === '') {
            return null;
        }

        // Arabic-Indic and Persian digits typed on an Arabic keyboard.
        $number = strtr($number, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        $number = preg_replace('/[\s\-().\x{200E}\x{200F}]/u', '', $number);

        if (str_starts_with($number, '+')) {
            $number = substr($number, 1);
        } elseif (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        } elseif (str_starts_with($number, '0')) {
            $number = config('whatsapp.default_country_code', '970').substr($number, 1);
        }

        // E.164: a country code that does not start with 0, at most 15 digits in all.
        return preg_match('/^[1-9][0-9]{9,14}$/', $number) === 1 ? $number : null;
    }

    /**
     * Whether two normalised numbers reach the same phone. A Palestinian line works under both +970 and +972, and
     * WhatsApp may report it under either one, so 970599123456 and 972599123456 count as the same shop.
     */
    public static function sameSubscriber(?string $a, ?string $b): bool
    {
        $a = self::normalize($a);
        $b = self::normalize($b);
        if ($a === null || $b === null) {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        return preg_match('/^97[02](\d+)$/', $a, $first) === 1
            && preg_match('/^97[02](\d+)$/', $b, $second) === 1
            && $first[1] === $second[1];
    }
}
