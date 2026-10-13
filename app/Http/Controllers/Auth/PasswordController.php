<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PasswordChangeCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'أدخل رمز التحقق المرسل إلى بريدك الإلكتروني.',
            'code.digits' => 'رمز التحقق مكوّن من 6 أرقام.',
        ]);

        if (! PasswordChangeCode::verify($request->user(), $validated['code'])) {
            return back()->withErrors(['code' => 'رمز التحقق غير صحيح أو منتهي. اطلب رمزًا جديدًا.'], 'updatePassword');
        }

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
