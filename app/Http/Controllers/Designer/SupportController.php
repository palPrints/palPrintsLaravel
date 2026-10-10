<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Support\PlatformSettings;
use App\Support\SupportTicketStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    private const CATEGORIES = [
        'account' => 'الحساب والملف الشخصي',
        'designs' => 'مراجعة التصاميم',
        'earnings' => 'الأرباح والسحب',
        'technical' => 'مشكلة تقنية',
        'other' => 'أخرى',
    ];

    public function show(Request $request): View
    {
        $tickets = SupportTicketStore::all()
            ->where('user_id', $request->user()->id)
            ->values();

        return view('designer.support', [
            'categories' => self::CATEGORIES,
            'tickets' => $tickets,
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
            'attachment' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
        ]);

        $user = $request->user();

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('support-attachments', 'local')
            : null;

        SupportTicketStore::create([
            'user_id' => $user->id,
            'role' => 'designer',
            'role_label' => 'مصمم',
            'name' => $user->name,
            'email' => $user->email,
            'category' => self::CATEGORIES[$validated['category']],
            'reference' => $validated['reference'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'attachment_path' => $attachmentPath,
        ]);

        return redirect()
            ->route('designer.support')
            ->with('status', 'تم إرسال طلبك إلى فريق الدعم، وسنرد عليك من خلال الإشعارات.');
    }
}
