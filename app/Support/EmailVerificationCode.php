<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;

/** The code that verifies a user's email address, instead of a link (see OneTimeCode). */
class EmailVerificationCode
{
    private const PURPOSE = 'email-verification';

    /** Seconds still to wait before another code can be sent, or 0 once the code was mailed. */
    public static function send(User $user): int
    {
        return OneTimeCode::send($user, self::PURPOSE, fn (string $code, int $minutes) => $user->notify(
            new EmailVerificationCodeNotification($user->name, $code, $minutes)
        ));
    }

    public static function verify(User $user, string $code): bool
    {
        return OneTimeCode::verify($user, self::PURPOSE, $code);
    }
}
