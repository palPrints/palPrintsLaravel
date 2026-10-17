<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.profile', ['admin' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge([
            'phone' => preg_replace('/[\s-]+/', '', (string) $request->input('phone')) ?: null,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'regex:/^05[69]\d{7}$/'],
        ], [
            'name.required' => 'أدخلي الاسم الكامل.',
            'email.required' => 'أدخلي البريد الإلكتروني.',
            'email.email' => 'أدخلي بريدًا إلكترونيًا صحيحًا.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'phone.regex' => 'يجب أن يبدأ رقم الهاتف بـ 059 أو 056 ويتكوّن من 10 أرقام.',
        ]);

        $user->fill([
            'name' => trim($validated['name']),
            'email' => mb_strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('admin.profile')->with('profile_status', 'تم حفظ بيانات الملف الشخصي.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.required' => 'أدخلي كلمة المرور الحالية.',
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.required' => 'أدخلي كلمة المرور الجديدة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return redirect()->route('admin.profile')->with('profile_status', 'تم تغيير كلمة المرور.');
    }
}
