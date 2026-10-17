<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\PlatformSettings;
use App\Support\SitePages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    /** Payment method key => [label, icon, css class]. */
    private const PAYMENT_METHODS = [
        'jawwal_pay' => ['Jawwal Pay', 'bi-phone', 'is-blue', 'محفظة إلكترونية'],
        'palpay' => ['PalPay', 'bi-wallet2', 'is-green', 'دفع إلكتروني'],
        'bank_of_palestine' => ['Bank of Palestine', 'bi-bank', 'is-navy', 'بطاقات وحوالات بنكية'],
    ];

    /** Action prefix => [icon, css class]. */
    private const AUDIT_ICONS = [
        'admin.' => ['bi-gear', 'is-blue'],
        'settings.' => ['bi-gear', 'is-blue'],
        'approval.' => ['bi-person-check', 'is-orange'],
        'account.' => ['bi-person-plus', 'is-green'],
    ];

    public function index(): View
    {
        return view('admin.settings', [
            'settings' => PlatformSettings::all(),
            'paymentMethods' => self::PAYMENT_METHODS,
            'pageTexts' => collect(SitePages::PAGES)->map(fn (array $page, string $key) => [
                'label' => $page[0],
                'text' => SitePages::editorText($key),
                'custom' => SitePages::custom($key) !== '',
            ]),
            'pageTokens' => SitePages::TOKENS,
            'auditLogs' => Schema::hasTable('audit_logs')
                ? AuditLog::with('user:id,name')->latest()->limit(20)->get()->map(fn (AuditLog $log) => $this->auditRow($log))
                : collect(),
        ]);
    }

    public function updateGeneral(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'currency' => ['required', Rule::in(['ILS', 'USD', 'JOD'])],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['allow_registration'] = $request->boolean('allow_registration');
        $validated['maintenance_mode'] = $request->boolean('maintenance_mode');

        PlatformSettings::update('general', $validated);

        $this->logChange($request, 'settings.general_updated', 'تحديث الإعدادات العامة للمنصة.');

        return response()->json(['ok' => true, 'message' => 'تم حفظ الإعدادات العامة.']);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $keys = ['order_updates', 'approval_results', 'payments_withdrawals', 'marketing'];
        $values = collect($keys)->mapWithKeys(fn (string $key) => [$key => $request->boolean($key)])->all();

        PlatformSettings::update('notifications', $values);

        $this->logChange($request, 'settings.notifications_updated', 'تحديث إعدادات الإشعارات.');

        return response()->json(['ok' => true, 'message' => 'تم حفظ إعدادات الإشعارات.']);
    }

    public function updateFees(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform_commission' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_processing_fee' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimum_withdrawal' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        PlatformSettings::update('fees', $validated);

        $this->logChange($request, 'settings.fees_updated', 'تحديث الرسوم والعمولات.');

        return response()->json(['ok' => true, 'message' => 'تم تحديث الرسوم.']);
    }

    public function updatePage(Request $request, string $page): JsonResponse
    {
        if (! array_key_exists($page, SitePages::PAGES)) {
            abort(404);
        }

        $validated = $request->validate(['content' => ['nullable', 'string', 'max:40000']]);
        $content = trim(str_replace(["\r\n", "\r"], "\n", (string) ($validated['content'] ?? '')));

        // Saving the unchanged default text would freeze it; treat it as "no custom text".
        if ($content === SitePages::defaultText($page)) {
            $content = '';
        }

        PlatformSettings::update('pages', [$page => $content]);

        $label = SitePages::PAGES[$page][0];
        $this->logChange($request, 'settings.page_updated', 'تحديث نص صفحة «'.$label.'».');

        return response()->json(['ok' => true, 'message' => $content === '' ? 'تمت استعادة النص الافتراضي لصفحة '.$label.'.' : 'تم حفظ صفحة '.$label.'.']);
    }

    public function updateSite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_cost' => ['required', 'numeric', 'min:0', 'max:10000'],
            'return_days' => ['required', 'integer', 'min:0', 'max:365'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s-]*$/'],
        ]);

        PlatformSettings::update('fees', ['shipping_cost' => (float) $validated['shipping_cost']]);
        PlatformSettings::update('site', [
            'return_days' => (int) $validated['return_days'],
            'instagram' => (string) ($validated['instagram'] ?? ''),
            'facebook' => (string) ($validated['facebook'] ?? ''),
            'tiktok' => (string) ($validated['tiktok'] ?? ''),
            'whatsapp' => (string) ($validated['whatsapp'] ?? ''),
        ]);

        $this->logChange($request, 'settings.site_updated', 'تحديث رسوم الشحن ومهلة الاسترجاع وروابط التواصل.');

        return response()->json(['ok' => true, 'message' => 'تم حفظ بيانات الموقع.']);
    }

    public function updatePaymentMethod(Request $request, string $method): JsonResponse
    {
        if (! array_key_exists($method, self::PAYMENT_METHODS)) {
            abort(404);
        }

        $enabled = $request->boolean('enabled');

        // Customers must always have a way to pay, so the last method that is on cannot be switched off.
        $othersOn = collect(array_keys(self::PAYMENT_METHODS))
            ->reject(fn (string $key) => $key === $method)
            ->contains(fn (string $key) => (bool) PlatformSettings::get('payments', $key, false));

        if (! $enabled && ! $othersOn) {
            return response()->json(['ok' => false, 'message' => 'يجب أن تبقى وسيلة دفع واحدة على الأقل مفعّلة.'], 422);
        }

        PlatformSettings::update('payments', [$method => $enabled]);

        $this->logChange(
            $request,
            'settings.payment_method_toggled',
            ($enabled ? 'تفعيل' : 'تعطيل').' وسيلة الدفع '.self::PAYMENT_METHODS[$method][0].'.'
        );

        return response()->json(['ok' => true, 'message' => $enabled ? 'تم تفعيل وسيلة الدفع.' : 'تم تعطيل وسيلة الدفع.']);
    }

    private function logChange(Request $request, string $action, string $description): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function auditRow(AuditLog $log): array
    {
        [$icon, $class] = collect(self::AUDIT_ICONS)
            ->first(fn ($pair, $prefix) => str_starts_with($log->action, $prefix)) ?? ['bi-clock-history', 'is-blue'];

        return [
            'title' => $log->user?->name ?? 'النظام',
            'description' => $log->description,
            'icon' => $icon,
            'class' => $class,
            'date' => $log->created_at?->locale('ar')->diffForHumans() ?? '—',
        ];
    }
}
