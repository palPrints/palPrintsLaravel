<?php

namespace App\Support;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * A 6-digit code mailed to a user for one purpose (confirm a password change, verify the email address).
 * Only a hash of the code is cached; it expires, is single use, and locks after a few wrong tries.
 */
class OneTimeCode
{
    public const TTL_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    private const RESEND_SECONDS = 60;

    /**
     * Makes a new code and hands it to $deliver (code, minutes) to be mailed.
     * Returns the seconds the user must still wait before asking again, or 0 once the code was sent.
     */
    public static function send(User $user, string $purpose, Closure $deliver): int
    {
        $wait = (int) Cache::get(self::cooldownKey($user, $purpose), 0) - now()->timestamp;

        if ($wait > 0) {
            return $wait;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(self::key($user, $purpose), ['hash' => Hash::make($code), 'attempts' => 0], now()->addMinutes(self::TTL_MINUTES));
        Cache::put(self::cooldownKey($user, $purpose), now()->addSeconds(self::RESEND_SECONDS)->timestamp, self::RESEND_SECONDS);

        $deliver($code, self::TTL_MINUTES);

        return 0;
    }

    /** True (and the code is used up) when $code matches the last code mailed to the user for this purpose. */
    public static function verify(User $user, string $purpose, string $code): bool
    {
        $entry = Cache::get(self::key($user, $purpose));

        if (! is_array($entry) || $entry['attempts'] >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $entry['hash'])) {
            $entry['attempts']++;
            Cache::put(self::key($user, $purpose), $entry, now()->addMinutes(self::TTL_MINUTES));

            return false;
        }

        Cache::forget(self::key($user, $purpose));

        return true;
    }

    private static function key(User $user, string $purpose): string
    {
        return 'one-time-code:'.$purpose.':'.$user->getKey();
    }

    private static function cooldownKey(User $user, string $purpose): string
    {
        return 'one-time-code-cooldown:'.$purpose.':'.$user->getKey();
    }
}
