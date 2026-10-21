<?php

namespace App\Support;

use App\Models\StickerSetting;

/**
 * The paper types the platform sells for paper printing ("عادي 80 جم", "مصقول 150 جم", ...). The admin keeps this list; a print
 * shop only chooses which of them it sells and writes the prices (see PaperPrices).
 *
 * The list is one row of the key/value settings table (no table of its own), so until the admin saves it the three original
 * types are used. A type's key is what prices, carts and orders store: the originals keep standard / thick / coated, a new
 * one gets p1, p2, ... Keys are never reused or renamed, so a price or an old order always points at the same type.
 * A hidden type stays in the list (its name still shows on old orders) but is no longer offered to shops or customers.
 */
class PaperTypes
{
    public const KEY = 'paper_types';

    public const MAX = 30;

    /** @return array<int, array{key: string, label: string, note: string, icon: string, active: bool}> */
    public static function defaults(): array
    {
        return [
            ['key' => 'standard', 'label' => 'عادي 80 جم', 'note' => 'للمستندات اليومية', 'icon' => 'bi-file-earmark', 'active' => true],
            ['key' => 'thick', 'label' => 'فاخر 120 جم', 'note' => 'أكثر سماكة', 'icon' => 'bi-file-earmark-richtext', 'active' => true],
            ['key' => 'coated', 'label' => 'مصقول 150 جم', 'note' => 'ألوان أوضح', 'icon' => 'bi-stars', 'active' => true],
        ];
    }

    /** Every type, hidden ones too, in the order the admin set. @return array<int, array{key: string, label: string, note: string, icon: string, active: bool}> */
    public static function all(): array
    {
        $saved = StickerSetting::query()->where('key', self::KEY)->first()?->value;

        if (! is_array($saved) || ! isset($saved['types']) || ! is_array($saved['types'])) {
            return self::defaults();
        }

        $types = [];
        foreach ($saved['types'] as $type) {
            if (is_array($type) && preg_match('/^[a-z0-9]{1,12}$/', (string) ($type['key'] ?? '')) && trim((string) ($type['label'] ?? '')) !== '') {
                $types[] = [
                    'key' => (string) $type['key'],
                    'label' => trim((string) $type['label']),
                    'note' => trim((string) ($type['note'] ?? '')),
                    'icon' => 'bi-file-earmark-text',
                    'active' => (bool) ($type['active'] ?? true),
                ];
            }
        }

        // The icons of the original three stay as they were.
        $icons = array_column(self::defaults(), 'icon', 'key');
        foreach ($types as &$type) {
            $type['icon'] = $icons[$type['key']] ?? $type['icon'];
        }

        return $types === [] ? self::defaults() : $types;
    }

    /** key => name of the types shops and customers can use now. @return array<string, string> */
    public static function active(): array
    {
        return collect(self::all())->where('active', true)->pluck('label', 'key')->all();
    }

    /** key => name of every type, hidden ones too (to show an old order). @return array<string, string> */
    public static function labels(): array
    {
        return collect(self::all())->pluck('label', 'key')->all();
    }

    /**
     * Saves the list the admin sent. Each entry is {key?, label, note, active}; an entry without a key is a new type.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array{key: string, label: string, note: string, icon: string, active: bool}>
     */
    public static function save(array $entries): array
    {
        $used = array_column(self::all(), 'key');
        $next = 1;
        foreach ($used as $key) {
            if (preg_match('/^p(\d+)$/', $key, $match)) {
                $next = max($next, (int) $match[1] + 1);
            }
        }

        $types = [];
        foreach ($entries as $entry) {
            $key = (string) ($entry['key'] ?? '');
            if ($key === '' || ! in_array($key, $used, true)) {
                $key = 'p'.$next++;
            }

            $types[] = [
                'key' => $key,
                'label' => trim((string) $entry['label']),
                'note' => trim((string) ($entry['note'] ?? '')),
                'active' => (bool) ($entry['active'] ?? true),
            ];
        }

        // A type that already has prices or orders is never dropped, only hidden: sent without it, it comes back hidden.
        foreach (self::all() as $old) {
            if (! in_array($old['key'], array_column($types, 'key'), true)) {
                $types[] = ['key' => $old['key'], 'label' => $old['label'], 'note' => $old['note'], 'active' => false];
            }
        }

        StickerSetting::query()->updateOrCreate(['key' => self::KEY], ['value' => ['types' => $types]]);

        return self::all();
    }
}
