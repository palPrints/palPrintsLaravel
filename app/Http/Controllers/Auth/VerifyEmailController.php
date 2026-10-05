<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\EmailVerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /** Marks the signed-in user's email as verified when the code mailed to them is entered. */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            $validated = $request->validate([
                'code' => ['required', 'digits:6'],
            ], [
                'code.required' => 'أدخل رمز التوثيق المرسل إلى بريدك الإلكتروني.',
                'code.digits' => 'رمز التوثيق مكوّن من 6 أرقام.',
            ]);

            if (! EmailVerificationCode::verify($user, $validated['code'])) {
                return back()->withErrors(['code' => 'رمز التوثيق غير صحيح أو منتهي. اطلب رمزًا جديدًا.']);
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        if ($user->hasRole('customer')) {
            return redirect()->route('customer.profile')->with('status', 'email-verified');
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
