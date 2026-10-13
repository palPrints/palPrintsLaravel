<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\EmailVerificationCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /** Mails a new verification code and sends the user to the page where it is entered. */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            if ($user->hasRole('customer')) {
                return redirect()->route('customer.profile')->with('status', 'email-verified');
            }

            return redirect()->intended(route('dashboard', absolute: false));
        }

        $wait = EmailVerificationCode::send($user);

        // Asking again within the minute just takes the user back to the code page, with the wait shown.
        return redirect()->route('verification.notice')
            ->with('status', $wait > 0 ? 'verification-code-wait' : 'verification-code-sent')
            ->with('verification_wait', $wait);
    }
}
