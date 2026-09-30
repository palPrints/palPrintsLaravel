<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\PrintProvider;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    private const DAYS = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

    /** Required verification documents: print_providers column => label. */
    private const DOCUMENT_LABELS = [
        'license_document' => 'رخصة المطبعة',
        'verification_document' => 'وثيقة التحقق',
        'id_document' => 'صورة الهوية',
    ];

    /**
     * Documents the current schema can store. `id_document` is only offered once the column exists.
     *
     * @return array<string, string>
     */
    private static function documentFields(): array
    {
        return array_filter(
            self::DOCUMENT_LABELS,
            fn (string $label, string $column) => Schema::hasColumn('print_providers', $column),
            ARRAY_FILTER_USE_BOTH,
        );
    }

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
            'documentLabels' => self::DOCUMENT_LABELS,
            'documentFields' => array_keys(self::documentFields()),
            'approvalStatus' => $provider->approval_status ?? 'draft',
            'adminNote' => $provider->admin_notes ?: $provider->rejection_reason,
        ]);
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<string, string>
     */
    private static function documentMessages(array $fields): array
    {
        $messages = [];

        foreach ($fields as $column => $label) {
            $messages[$column.'.mimes'] = $label.' يجب أن تكون صورة (JPG, PNG, WEBP) أو ملف PDF.';
            $messages[$column.'.max'] = 'حجم '.$label.' يجب ألا يتجاوز 5 ميجابايت.';
            $messages[$column.'.uploaded'] = 'تعذر رفع '.$label.'، حاول مرة أخرى.';
        }

        return $messages;
    }

    public function update(Request $request): RedirectResponse
    {
        // A profile that is already with the admin for review cannot change underneath them.
        if ($request->user()->isAwaitingApproval()) {
            return redirect()
                ->route('print-provider.profile')
                ->with('warning', 'ملفك قيد المراجعة حاليًا، ولا يمكن تعديله حتى تصدر الإدارة قرارها.');
        }

        $request->merge([
            'contact_name' => trim((string) $request->input('contact_name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'company_name' => trim((string) $request->input('company_name')),
        ]);

        $documentFields = self::documentFields();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
            'contact_name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($request->user())],
            'phone' => ['required', 'string', 'max:30'],
            'owner_phone' => ['required', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            ...array_map(
                fn () => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
                $documentFields,
            ),
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
            'phone.required' => 'أدخل رقم هاتف المطبعة.',
            'owner_phone.required' => 'أدخل رقم هاتف صاحب المطبعة.',
            'address.required' => 'أدخل العنوان التفصيلي.',
        ] + self::documentMessages($documentFields));

        $user = $request->user();
        $provider = $user->printProvider ?? new PrintProvider(['user_id' => $user->id]);

        $oldPaths = [];
        $newPaths = [];

        foreach (array_keys($documentFields) as $field) {
            $oldPaths[$field] = $provider->$field;

            if ($request->hasFile($field)) {
                $newPaths[$field] = $request->file($field)->store('print-provider/documents', 'public');
            }
        }

        try {
            DB::transaction(function () use ($user, $validated, $request, $newPaths, $oldPaths, $documentFields): void {
                $user->fill([
                    'name' => $validated['contact_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['owner_phone'],
                ]);

                if ($user->isDirty('email')) {
                    $user->email_verified_at = null;
                }

                $user->save();

                $documents = [];
                foreach (array_keys($documentFields) as $field) {
                    $documents[$field] = $newPaths[$field] ?? $oldPaths[$field];
                }

                $user->printProvider()->updateOrCreate(['user_id' => $user->id], $documents + [
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
                    // The profile only counts as complete once every required document is on file.
                    'profile_completed_at' => count(array_filter($documents)) === count($documents) ? now() : null,
                ]);
            });
        } catch (Throwable $exception) {
            foreach ($newPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        // Replaced files are removed only after the new ones are safely saved.
        foreach ($newPaths as $field => $path) {
            if (filled($oldPaths[$field] ?? null)) {
                Storage::disk('public')->delete($oldPaths[$field]);
            }
        }

        return redirect()
            ->route('print-provider.profile')
            ->with('profile_status', 'updated');
    }
}
