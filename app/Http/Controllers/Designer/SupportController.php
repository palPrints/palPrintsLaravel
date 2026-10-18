<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Concerns\HandlesSupportTickets;
use App\Http\Controllers\Controller;
use App\Support\PlatformSettings;
use App\Support\SupportTickets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    use HandlesSupportTickets;

    private const CATEGORIES = [
        'account' => 'الحساب والملف الشخصي',
        'designs' => 'مراجعة التصاميم',
        'earnings' => 'الأرباح والسحب',
        'technical' => 'مشكلة تقنية',
        'other' => 'أخرى',
    ];

    public function show(Request $request): View
    {
        return view('designer.support', [
            'categories' => self::CATEGORIES,
            'tickets' => $this->ownTickets($request),
            'contactPhone' => PlatformSettings::get('general', 'contact_phone'),
            'contactEmail' => PlatformSettings::get('general', 'admin_email', config('mail.from.address')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(self::CATEGORIES))],
            'reference' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $ticket = SupportTickets::create(
            $request->user(),
            self::CATEGORIES[$validated['category']],
            $validated['subject'],
            $validated['message'],
            $validated['reference'] ?? null,
        );

        return redirect()
            ->to(SupportTickets::supportUrl($request->user(), $ticket))
            ->with('status', 'تم إرسال طلبك إلى فريق الدعم، وسنرد عليك من خلال الإشعارات.');
    }
}
