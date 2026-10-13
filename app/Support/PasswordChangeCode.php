<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\PasswordChangeCodeNotification;

/** The code that must accompany a password change (see OneTimeCode). */
class PasswordChangeCode
{
    public const TTL_MINUTES = OneTimeCode::TTL_MINUTES;

    private const PURPOSE = 'password-change';

    /** Seconds still to wait before another code can be sent, or 0 once the code was mailed. */
    public static function send(User $user): int
    {
        return OneTimeCode::send($user, self::PURPOSE, fn (string $code, int $minutes) => $user->notify(
            new PasswordChangeCodeNotification($user->name, $code, $minutes)
        ));
    }

    public static function verify(User $user, string $code): bool
    {
        return OneTimeCode::verify($user, self::PURPOSE, $code);
    }
}
