<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->loadMissing('designerProfile');
        $profile = $user->designerProfile;

        return view('designer.settings', [
            'user' => $user,
            'profile' => $profile,
            'approvalStatus' => $user->approvalStatus(),
            'profileComplete' => $profile?->profile_completed_at !== null,
            'hasPortfolio' => filled($profile?->portfolio_url),
            'adminNote' => $profile?->admin_notes ?: $profile?->rejection_reason,
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

        DB::transaction(function () use ($user, $validated): void {
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $user->designerProfile()->update(['full_name' => $validated['name']]);
        });

        return redirect()
            ->route('designer.settings')
            ->with('settings_status', 'account-updated');
    }
}
