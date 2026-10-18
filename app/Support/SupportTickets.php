<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Support tickets on the support_tickets / support_messages tables: one ticket per problem, a thread of messages
 * between the user (customer, designer or print shop) and the admins.
 *
 * Status flow: pending_admin (waiting for the admins) -> pending_user (admin replied) -> pending_admin (user replied)
 * ... -> closed (admin marked it solved). The admin page shows them as new / open / resolved.
 */
class SupportTickets
{
    public const ROLES = [
        'customer' => ['label' => 'عميل', 'route' => 'customer.support', 'view' => 'customer.support-chat'],
        'designer' => ['label' => 'مصمم', 'route' => 'designer.support', 'view' => 'designer.support-chat'],
        'printer' => ['label' => 'مطبعة', 'route' => 'print-provider.support', 'view' => 'printProvider.support-chat'],
    ];

    public static function roleKey(?User $user): string
    {
        return match (true) {
            $user?->hasRole('designer') => 'designer',
            $user?->hasRole('print_provider') => 'printer',
            default => 'customer',
        };
    }

    public static function supportUrl(?User $user, ?SupportTicket $ticket = null): string
    {
        $route = self::ROLES[self::roleKey($user)]['route'];

        return $ticket ? route($route.'.show', $ticket) : route($route);
    }

    /** new = waiting for the admins, open = being handled, resolved = closed. */
    public static function statusKey(SupportTicket $ticket): string
    {
        return match ($ticket->status) {
            SupportTicket::STATUS_PENDING_ADMIN => 'new',
            SupportTicket::STATUS_CLOSED => 'resolved',
            default => 'open',
        };
    }

    public static function create(User $user, string $category, string $subject, string $message, ?string $reference = null): SupportTicket
    {
        $ticket = DB::transaction(function () use ($user, $category, $subject, $message, $reference) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'subject' => $subject,
                'category' => mb_substr($category, 0, 40),
                'status' => SupportTicket::STATUS_PENDING_ADMIN,
                'last_reply_at' => now(),
            ]);

            $ticket->messages()->create([
                'sender_id' => $user->id,
                'message' => $reference ? $message."\n\nرقم الطلب أو المرجع: ".$reference : $message,
                'is_admin_reply' => false,
            ]);

            return $ticket;
        });

        AdminNotifier::toAdmins(
            'support.new',
            'طلب دعم جديد',
            sprintf('أرسل %s (%s) طلب دعم: %s', $user->name, self::ROLES[self::roleKey($user)]['label'], $subject),
            self::adminUrl($ticket),
        );

        return $ticket;
    }

    /** A reply from the ticket's owner. Waits for the admins again. */
    public static function replyAsUser(SupportTicket $ticket, User $user, string $message): SupportMessage
    {
        $reply = $ticket->messages()->create(['sender_id' => $user->id, 'message' => $message, 'is_admin_reply' => false]);

        $ticket->update(['status' => SupportTicket::STATUS_PENDING_ADMIN, 'last_reply_at' => now()]);

        AdminNotifier::toAdmins(
            'support.new',
            'رد جديد على طلب دعم',
            sprintf('رد %s على طلب الدعم: %s', $user->name, $ticket->subject),
            self::adminUrl($ticket),
        );

        return $reply;
    }

    public static function replyAsAdmin(SupportTicket $ticket, User $admin, string $message): SupportMessage
    {
        $reply = $ticket->messages()->create(['sender_id' => $admin->id, 'message' => $message, 'is_admin_reply' => true]);

        $ticket->update(['status' => SupportTicket::STATUS_PENDING_USER, 'last_reply_at' => now(), 'closed_at' => null]);

        self::notifyOwner($ticket, 'تم الرد على طلب الدعم', $message);

        return $reply;
    }

    /** $key is the admin page's status: new, open or resolved. */
    public static function setStatus(SupportTicket $ticket, string $key): void
    {
        $ticket->update(match ($key) {
            'resolved' => ['status' => SupportTicket::STATUS_CLOSED, 'closed_at' => now()],
            'new' => ['status' => SupportTicket::STATUS_PENDING_ADMIN, 'closed_at' => null],
            default => ['status' => SupportTicket::STATUS_OPEN, 'closed_at' => null],
        });

        if ($key === 'resolved') {
            self::notifyOwner($ticket, 'تم حل طلب الدعم', 'تم تحديد طلبك «'.$ticket->subject.'» كمحلول من فريق الدعم.');
        }
    }

    public static function notifyOwner(SupportTicket $ticket, string $title, string $message): void
    {
        $owner = $ticket->user;

        if (! $owner) {
            return;
        }

        Notification::create([
            'user_id' => $owner->id,
            'type' => 'support.reply',
            'title' => $title,
            'message' => $message,
            'link' => self::supportUrl($owner, $ticket),
        ]);
    }

    /** Whoever opens a ticket has seen what its notifications said: they stop counting on the bell and the sidebar badge. */
    public static function markSeen(SupportTicket $ticket, User $viewer): void
    {
        $viewer->userNotifications()
            ->where('is_read', false)
            ->where('type', 'like', 'support.%')
            ->whereIn('link', [
                self::supportUrl($viewer, $ticket),
                self::adminUrl($ticket),
                route('admin.support', ['open' => $ticket->id]),
            ])
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public static function pendingAdminCount(): int
    {
        return SupportTicket::where('status', SupportTicket::STATUS_PENDING_ADMIN)->count();
    }

    /** The ticket's own chat page for the admins. */
    public static function adminUrl(SupportTicket $ticket): string
    {
        return route('admin.support.show', $ticket);
    }

    /** One ticket with its whole conversation, as the pages' scripts read it. */
    public static function thread(SupportTicket $ticket): array
    {
        $ticket->loadMissing(['messages.sender', 'user']);

        return [
            'id' => $ticket->id,
            'number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'status' => self::statusKey($ticket),
            'closed' => $ticket->status === SupportTicket::STATUS_CLOSED,
            'messages' => $ticket->messages->map(fn (SupportMessage $message) => [
                'id' => $message->id,
                'text' => $message->message,
                'fromAdmin' => $message->is_admin_reply,
                'sender' => $message->is_admin_reply ? 'إدارة PalPrints' : ($message->sender?->name ?? $ticket->user?->name ?? '—'),
                'time' => $message->created_at?->locale('ar')->diffForHumans(),
                'at' => $message->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
