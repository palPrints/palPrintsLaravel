<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\BranchProductOffering;
use App\Models\PrintProvider;
use App\Models\PrintProviderBranch;
use App\Models\Product;
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

    /** Extra services a print shop can offer (plus its own "other" entries); the products it prints are chosen from the catalog. */
    private const SERVICE_OPTIONS = [
        'rush' => 'طباعة مستعجلة',
        'delivery' => 'التوصيل للعميل',
    ];

    /** Required verification documents: print_providers column => label. */
    private const DOCUMENT_LABELS = [
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
            'hasServices' => Schema::hasColumn('print_providers', 'services'),
            'serviceOptions' => self::SERVICE_OPTIONS,
            'selectedProductIds' => $provider->exists
                ? BranchProductOffering::query()
                    ->whereIn('print_provider_branch_id', PrintProviderBranch::where('print_provider_id', $provider->id)->select('id'))
                    ->where(fn ($query) => $query->where('is_active', true)->orWhere('base_price', 0))
                    ->pluck('product_id')->unique()->map(fn ($id) => (int) $id)->values()->all()
                : [],
            'productOptions' => Product::query()->where('is_active', true)->with('category:id,name')->orderBy('category_id')->orderBy('name')->get(['id', 'name', 'category_id', 'image']),
            'hasContactEmail' => Schema::hasColumn('print_providers', 'contact_email'),
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

    /**
     * The products a shop says it prints are kept as its offerings. A product it ticks for the first time starts inactive with
     * no price, so nothing reaches the site until the shop completes price, production time and capacity. One it already
     * priced is switched back on when ticked and switched off (not deleted, orders keep pointing at it) when unticked.
     *
     * @param  array<int, int>  $productIds
     */
    private function syncChosenProducts(PrintProvider $provider, array $productIds): void
    {
        $listed = Product::query()->where('is_active', true)->pluck('id');   // what the form offers; other offerings are left alone
        $wanted = $listed->intersect($productIds)->values();
        if (! $provider->branches()->exists() && $wanted->isEmpty()) {
            return;
        }

        $branch = $provider->primaryBranch();

        $offerings = BranchProductOffering::query()
            ->whereIn('print_provider_branch_id', PrintProviderBranch::where('print_provider_id', $provider->id)->select('id'))
            ->get();

        foreach ($offerings->whereIn('product_id', $listed) as $offering) {
            $chosen = $wanted->contains($offering->product_id);
            $configured = (float) $offering->base_price > 0;

            if ($chosen && $configured && ! $offering->is_active) {
                $offering->update(['is_active' => true]);
            } elseif (! $chosen && $configured && $offering->is_active) {
                $offering->update(['is_active' => false]);
            } elseif (! $chosen && ! $configured && ! $offering->orderItems()->exists()) {
                $offering->delete();
            }
        }

        foreach ($wanted->diff($offerings->pluck('product_id')) as $productId) {
            $branch->branchProductOfferings()->create([
                'product_id' => $productId, 'base_price' => 0, 'currency' => 'ILS',
                'production_time_min' => 0, 'production_time_max' => 0, 'daily_capacity' => 0, 'is_active' => false,
            ]);
        }
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
            'contact_email' => Str::lower(trim((string) $request->input('contact_email'))) ?: null,
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
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', Rule::exists('products', 'id')->where('is_active', true)],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', Rule::in(array_keys(self::SERVICE_OPTIONS))],
            'other_services' => ['nullable', 'string', 'max:500'],
            // Only accepted once the contact_email column exists in the schema.
            'contact_email' => Schema::hasColumn('print_providers', 'contact_email')
                ? ['nullable', 'email', 'max:255']
                : ['prohibited'],
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

                $contactEmail = Schema::hasColumn('print_providers', 'contact_email')
                    ? ['contact_email' => $validated['contact_email'] ?? null]
                    : [];

                $services = Schema::hasColumn('print_providers', 'services')
                    ? ['services' => [
                        'selected' => array_values($validated['services'] ?? []),
                        'other' => collect(preg_split('/[,،\n]+/u', (string) ($validated['other_services'] ?? '')))
                            ->map(fn ($item) => Str::limit(trim((string) $item), 60, ''))
                            ->filter()->unique()->take(10)->values()->all(),
                    ]]
                    : [];

                $user->printProvider()->updateOrCreate(['user_id' => $user->id], $documents + $contactEmail + $services + [
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

                $this->syncChosenProducts($user->printProvider()->first(), array_map('intval', $validated['products'] ?? []));
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
