<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PasswordChangeCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordCodeController extends Controller
{
    /** Mails the signed-in user the code that must accompany a password change. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $wait = PasswordChangeCode::send($user);

        if ($wait > 0) {
            return response()->json(['message' => 'انتظر '.$wait.' ثانية قبل طلب رمز جديد.', 'retry_after' => $wait], 429);
        }

        return response()->json([
            'message' => 'أرسلنا رمز التحقق إلى '.$this->masked($user->email).'. الرمز صالح لمدة '.PasswordChangeCode::TTL_MINUTES.' دقائق.',
            'retry_after' => 60,
        ]);
    }

    private function masked(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 2).str_repeat('*', max(2, mb_strlen($local) - 2)).'@'.$domain;
    }
}
