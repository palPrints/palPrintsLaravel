<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Notifications\EmailAddressChanged;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('customer.profile', [
            'customer' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge([
            'phone' => preg_replace('/[\s-]+/', '', (string) $request->input('phone')) ?: null,
        ]);

        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'regex:/^05[69]\d{7}$/'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'fullName.required' => 'أدخلي الاسم الكامل.',
            'email.required' => 'أدخلي البريد الإلكتروني.',
            'email.email' => 'أدخلي بريدًا إلكترونيًا صحيحًا.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'phone.regex' => 'يجب أن يبدأ رقم الهاتف بـ 059 أو 056 ويتكوّن من 10 أرقام.',
            'avatar.image' => 'يجب أن تكون الصورة بصيغة صحيحة.',
            'avatar.max' => 'حجم الصورة كبير جدًا.',
        ]);

        $user->name = trim($validated['fullName']);
        $user->email = mb_strtolower(trim($validated['email']));
        $user->phone = $validated['phone'] ?? null;

        $oldEmail = $user->isDirty('email') ? $user->getOriginal('email') : null;

        if ($oldEmail !== null) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $oldAvatarPath = $user->avatar_path;
            $user->avatar_path = $request->file('avatar')->store('customer/avatars', 'public');

            if ($oldAvatarPath) {
                Storage::disk('public')->delete($oldAvatarPath);
            }
        }

        $user->save();

        if ($oldEmail !== null) {
            // Tell the old address, so the real owner notices an unwanted change. A mail problem must not undo the save.
            try {
                Notification::route('mail', $oldEmail)->notify(new EmailAddressChanged($user->name, $user->email));
            } catch (Throwable $e) {
                report($e);
            }

            // The new address starts unverified, so its verification link goes out right away.
            try {
                $user->sendEmailVerificationNotification();
                $verificationSent = true;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('customer.profile')
            ->with('status', 'profile-updated')
            ->with('verification_sent', $verificationSent ?? false);
    }
}
