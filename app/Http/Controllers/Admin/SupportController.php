<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Support\SupportTickets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        // Old notification links open a ticket as ?open=<id>; tickets now have their own page.
        if ($request->filled('open') && ($ticket = SupportTicket::find($request->query('open')))) {
            return redirect()->route('admin.support.show', $ticket);
        }

        $tickets = SupportTicket::with(['user', 'messages'])
            ->latest('last_reply_at')
            ->latest('id')
            ->get()
            ->map(fn (SupportTicket $ticket) => $this->present($ticket));

        return view('admin.support', [
            'tickets' => $tickets,
            'newCount' => SupportTickets::pendingAdminCount(),
            'counts' => [
                'active' => $tickets->where('status', '!=', 'resolved')->count(),
                'all' => $tickets->count(),
                'new' => $tickets->where('status', 'new')->count(),
                'open' => $tickets->where('status', 'open')->count(),
                'resolved' => $tickets->where('status', 'resolved')->count(),
            ],
        ]);
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $ticket->load(['user', 'messages']);

        SupportTickets::markSeen($ticket, $request->user());

        return view('admin.support-chat', [
            'ticket' => $ticket,
            'thread' => SupportTickets::thread($ticket),
            'person' => $this->present($ticket),
        ]);
    }

    public function messages(SupportTicket $ticket): JsonResponse
    {
        return response()->json(['ticket' => SupportTickets::thread($ticket)]);
    }

    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // A solved ticket is reopened on purpose (the status button), not by answering it.
        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            return response()->json(['message' => 'هذه التذكرة محلولة، أعد فتحها أولًا للرد عليها.'], 422);
        }

        SupportTickets::replyAsAdmin($ticket, $request->user(), trim($validated['message']));

        return response()->json([
            'ok' => true,
            'message' => 'تم إرسال الرد إلى المستخدم.',
            'ticket' => SupportTickets::thread($ticket->fresh()),
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['new', 'open', 'resolved'])],
        ]);

        SupportTickets::setStatus($ticket, $validated['status']);

        return response()->json([
            'ok' => true,
            'message' => 'تم تحديث حالة الطلب.',
            'ticket' => SupportTickets::thread($ticket->fresh()),
        ]);
    }

    private function present(SupportTicket $ticket): array
    {
        $role = SupportTickets::roleKey($ticket->user);
        $name = (string) ($ticket->user?->name ?? '—');

        return [
            'id' => $ticket->id,
            'number' => $ticket->ticket_number,
            'role' => $role,
            'role_label' => SupportTickets::ROLES[$role]['label'],
            'name' => $name,
            'initials' => mb_substr(trim($name), 0, 1) ?: '؟',
            'email' => (string) $ticket->user?->email,
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'status' => SupportTickets::statusKey($ticket),
            'message' => (string) $ticket->messages->first()?->message,
            'time' => $ticket->created_at?->locale('ar')->diffForHumans(),
            'last' => ($ticket->last_reply_at ?? $ticket->created_at)?->locale('ar')->diffForHumans(),
            'count' => $ticket->messages->count(),
            'url' => route('admin.support.show', $ticket),
            'replyUrl' => route('admin.support.reply', $ticket),
            'statusUrl' => route('admin.support.status', $ticket),
        ];
    }
}
