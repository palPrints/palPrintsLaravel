<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->loadMissing('printProvider');
        $provider = $user->printProvider;

        return view('printProvider.settings', [
            'user' => $user,
            'provider' => $provider,
            'approvalStatus' => $user->approvalStatus(),
            'profileComplete' => $provider?->profile_completed_at !== null,
            'adminNote' => $provider?->admin_notes ?: $provider?->rejection_reason,
        ]);
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => trim((string) $request->input('phone')) ?: null,
        ]);

        $validated = $request->validateWithBag('updateAccount', [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($request->user())],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'name.required' => 'أدخل الاسم الكامل.',
            'name.min' => 'يجب أن يتكوّن الاسم من 3 أحرف على الأقل.',
            'email.required' => 'أدخل البريد الإلكتروني.',
            'email.email' => 'أدخل بريدًا إلكترونيًا صحيحًا.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'phone.max' => 'يجب ألا يتجاوز رقم الهاتف 30 حرفًا.',
        ]);

        $user = $request->user();
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()
            ->route('print-provider.settings')
            ->with('settings_status', 'account-updated');
    }
}
