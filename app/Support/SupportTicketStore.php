<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Real, persisted support tickets — stored as a JSON file on the local disk
 * (no support_tickets table exists in the current schema yet).
 */
class SupportTicketStore
{
    private const PATH = 'support-tickets.json';

    public static function all(): Collection
    {
        if (! Storage::exists(self::PATH)) {
            return collect();
        }

        $stored = json_decode(Storage::get(self::PATH), true);

        return collect(is_array($stored) ? $stored : [])
            ->sortByDesc('created_at')
            ->values();
    }

    public static function find(string $id): ?array
    {
        return self::all()->firstWhere('id', $id);
    }

    public static function create(array $data): array
    {
        $tickets = self::readRaw();
        $number = count($tickets) + 1001;

        $ticket = array_merge([
            'id' => 'REQ-'.$number,
            'status' => 'new',
            'reply' => null,
            'replied_at' => null,
        ], $data, [
            'created_at' => now()->toIso8601String(),
        ]);

        $tickets[] = $ticket;
        self::writeRaw($tickets);

        AdminNotifier::toAdmins(
            'support.new',
            'طلب دعم جديد',
            sprintf('أرسل %s (%s) طلب دعم: %s', $ticket['name'] ?? '—', $ticket['role_label'] ?? '—', $ticket['subject'] ?? ''),
            route('admin.support'),
        );

        return $ticket;
    }

    public static function update(string $id, array $values): ?array
    {
        $tickets = self::readRaw();
        $updated = null;

        foreach ($tickets as &$ticket) {
            if ($ticket['id'] === $id) {
                $ticket = array_merge($ticket, $values);
                $updated = $ticket;
                break;
            }
        }
        unset($ticket);

        if ($updated !== null) {
            self::writeRaw($tickets);
        }

        return $updated;
    }

    private static function readRaw(): array
    {
        if (! Storage::exists(self::PATH)) {
            return [];
        }

        $stored = json_decode(Storage::get(self::PATH), true);

        return is_array($stored) ? $stored : [];
    }

    private static function writeRaw(array $tickets): void
    {
        Storage::put(self::PATH, json_encode(array_values($tickets), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
