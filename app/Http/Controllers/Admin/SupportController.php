<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Support\SupportTicketStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(): View
    {
        $tickets = SupportTicketStore::all()->map(fn (array $ticket) => $this->present($ticket));

        return view('admin.support', [
            'tickets' => $tickets,
            'newCount' => $tickets->where('status', 'new')->count(),
        ]);
    }

    public function reply(Request $request, string $ticket): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $record = SupportTicketStore::find($ticket);
        abort_if($record === null, 404);

        $updated = SupportTicketStore::update($ticket, [
            'status' => 'open',
            'reply' => $validated['message'],
            'replied_at' => now()->toIso8601String(),
        ]);

        $this->notifyUser($record, 'تم الرد على طلب الدعم', $validated['message']);

        return response()->json(['ok' => true, 'message' => 'تم إرسال الرد إلى المستخدم.', 'ticket' => $this->present($updated)]);
    }

    public function updateStatus(Request $request, string $ticket): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['new', 'open', 'resolved'])],
        ]);

        $record = SupportTicketStore::find($ticket);
        abort_if($record === null, 404);

        $updated = SupportTicketStore::update($ticket, ['status' => $validated['status']]);

        if ($validated['status'] === 'resolved') {
            $this->notifyUser($record, 'تم حل طلب الدعم', 'تم تحديد طلبك «'.$record['subject'].'» كمحلول من فريق الدعم.');
        }

        return response()->json(['ok' => true, 'message' => 'تم تحديث حالة الطلب.', 'ticket' => $this->present($updated)]);
    }

    private function notifyUser(array $ticket, string $title, string $message): void
    {
        if (empty($ticket['user_id'])) {
            return;
        }

        $recipient = User::find($ticket['user_id']);
        $route = match (true) {
            $recipient?->hasRole('customer') => 'customer.support',
            default => 'designer.support',
        };

        Notification::create([
            'user_id' => $ticket['user_id'],
            'type' => 'support.reply',
            'title' => $title,
            'message' => $message,
            'link' => route($route),
        ]);
    }

    private function present(array $ticket): array
    {
        $created = Carbon::parse($ticket['created_at']);

        return array_merge($ticket, [
            'initials' => mb_substr(trim((string) $ticket['name']), 0, 1) ?: '؟',
            'time' => $created->locale('ar')->diffForHumans(),
            'reviewUrl' => route('admin.support.reply', $ticket['id']),
            'statusUrl' => route('admin.support.status', $ticket['id']),
        ]);
    }
}
