<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SupportTicket;
use App\Support\SupportTickets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What the customer, designer and print shop support pages share: opening one of their own tickets as a
 * conversation, answering it, and listing their tickets. (Creating a ticket differs per page.)
 */
trait HandlesSupportTickets
{
    private function ownTickets(Request $request)
    {
        return SupportTicket::where('user_id', $request->user()->id)
            ->orderByRaw("status = 'closed'")
            ->latest('last_reply_at')
            ->latest('id')
            ->get();
    }

    /** The ticket as a chat page. */
    public function showTicket(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $role = SupportTickets::ROLES[SupportTickets::roleKey($request->user())];

        SupportTickets::markSeen($ticket, $request->user());

        return view($role['view'], [
            'ticket' => $ticket,
            'thread' => SupportTickets::thread($ticket),
            'backUrl' => route($role['route']),
            'messagesUrl' => route($role['route'].'.messages', $ticket),
            'replyUrl' => route($role['route'].'.reply', $ticket),
        ]);
    }

    /** The conversation as JSON, polled by the chat page so new replies show up without reloading. */
    public function ticketMessages(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        return response()->json(['ticket' => SupportTickets::thread($ticket)]);
    }

    public function replyTicket(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $validated = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            return response()->json(['message' => 'هذا الطلب مغلق، افتح طلب دعم جديد.'], 422);
        }

        SupportTickets::replyAsUser($ticket, $request->user(), trim($validated['message']));

        return response()->json(['message' => 'تم إرسال ردك إلى فريق الدعم.', 'ticket' => SupportTickets::thread($ticket->fresh())]);
    }
}
