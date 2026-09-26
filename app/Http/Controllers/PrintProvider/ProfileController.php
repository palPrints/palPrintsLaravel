<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\PrintProvider;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    private const DAYS = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

    public function show(Request $request): View
    {
        $user = $request->user();
        $provider = $user->printProvider ?? new PrintProvider([
            'company_name' => $user->name,
            'approval_status' => 'draft',
            'is_active' => true,
        ]);

        return view('printProvider.profile', [
            'user' => $user,
            'provider' => $provider,
            'hours' => array_merge([
                'available' => true,
                'days' => array_slice(self::DAYS, 0, 5),
                'from' => '08:00',
                'to' => '20:00',
            ], $provider->working_hours ?? []),
            'allDays' => self::DAYS,
            'approvalStatus' => $provider->approval_status ?? 'draft',
            'adminNote' => $provider->admin_notes ?: $provider->rejection_reason,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'contact_name' => trim((string) $request->input('contact_name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'company_name' => trim((string) $request->input('company_name')),
        ]);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
            'contact_name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($request->user())],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'available' => ['nullable', 'boolean'],
            'days' => ['nullable', 'array'],
            'days.*' => ['string', Rule::in(self::DAYS)],
            'from' => ['nullable', 'date_format:H:i'],
            'to' => ['nullable', 'date_format:H:i'],
        ], [
            'company_name.required' => 'أدخل اسم المطبعة.',
            'contact_name.required' => 'أدخل اسم المسؤول.',
            'email.required' => 'أدخل البريد الإلكتروني.',
            'email.email' => 'أدخل بريدًا إلكترونيًا صحيحًا.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'phone.required' => 'أدخل رقم الهاتف.',
            'address.required' => 'أدخل العنوان التفصيلي.',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $validated, $request): void {
            $user->fill(['name' => $validated['contact_name'], 'email' => $validated['email'], 'phone' => $validated['phone']]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $user->printProvider()->updateOrCreate(['user_id' => $user->id], [
                'company_name' => $validated['company_name'],
                'phone' => $validated['phone'],
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'address' => $validated['address'],
                'working_hours' => [
                    'available' => $request->boolean('available'),
                    'days' => array_values($validated['days'] ?? []),
                    'from' => $validated['from'] ?? '08:00',
                    'to' => $validated['to'] ?? '20:00',
                ],
                'profile_completed_at' => now(),
            ]);
        });

        return redirect()
            ->route('print-provider.profile')
            ->with('profile_status', 'updated');
    }
}
