<?php

namespace App\Support;

use App\Models\StickerSetting;

/**
 * The bindings the platform sells for paper printing (spiral, staples, lamination...). The admin keeps this list; a print shop
 * only chooses which of them it offers and writes the price (see PaperPrices). "No binding" is not in the list, it is always free.
 *
 * Kept exactly like PaperTypes: one row of the key/value settings table, the six original bindings until the admin saves it,
 * a new one gets b1, b2, ... and a key is never reused. A hidden binding stays in the list so an old order still shows its
 * name, but it is no longer offered to shops or customers.
 */
class PaperBindings
{
    public const KEY = 'paper_bindings';

    public const MAX = 30;

    /** @return array<int, array{key: string, label: string, active: bool}> */
    public static function defaults(): array
    {
        return [
            ['key' => 'spiral', 'label' => 'سلك حلزوني', 'active' => true],
            ['key' => 'clearCover', 'label' => 'غلاف شفاف', 'active' => true],
            ['key' => 'glue', 'label' => 'تجليد حراري', 'active' => true],
            ['key' => 'sideStaple', 'label' => 'تدبيس جانبي', 'active' => true],
            ['key' => 'cornerStaple', 'label' => 'تدبيس زاوية', 'active' => true],
            ['key' => 'lamination', 'label' => 'تغليف حراري شفاف', 'active' => true],
        ];
    }

    /** Every binding, hidden ones too, in the order the admin set. @return array<int, array{key: string, label: string, active: bool}> */
    public static function all(): array
    {
        $saved = StickerSetting::query()->where('key', self::KEY)->first()?->value;

        if (! is_array($saved) || ! isset($saved['bindings']) || ! is_array($saved['bindings'])) {
            return self::defaults();
        }

        $bindings = [];
        foreach ($saved['bindings'] as $binding) {
            if (is_array($binding) && preg_match('/^[A-Za-z0-9]{1,12}$/', (string) ($binding['key'] ?? '')) && trim((string) ($binding['label'] ?? '')) !== '') {
                $bindings[] = [
                    'key' => (string) $binding['key'],
                    'label' => trim((string) $binding['label']),
                    'active' => (bool) ($binding['active'] ?? true),
                ];
            }
        }

        return $bindings === [] ? self::defaults() : $bindings;
    }

    /** key => name of the bindings shops and customers can use now. @return array<string, string> */
    public static function active(): array
    {
        return collect(self::all())->where('active', true)->pluck('label', 'key')->all();
    }

    /** key => name of every binding, hidden ones too (to show an old order). @return array<string, string> */
    public static function labels(): array
    {
        return collect(self::all())->pluck('label', 'key')->all();
    }

    /**
     * Saves the list the admin sent. Each entry is {key?, label, active}; an entry without a key is a new binding.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array{key: string, label: string, active: bool}>
     */
    public static function save(array $entries): array
    {
        $used = array_column(self::all(), 'key');
        $next = 1;
        foreach ($used as $key) {
            if (preg_match('/^b(\d+)$/', $key, $match)) {
                $next = max($next, (int) $match[1] + 1);
            }
        }

        $bindings = [];
        foreach ($entries as $entry) {
            $key = (string) ($entry['key'] ?? '');
            if ($key === '' || ! in_array($key, $used, true)) {
                $key = 'b'.$next++;
            }

            $bindings[] = ['key' => $key, 'label' => trim((string) $entry['label']), 'active' => (bool) ($entry['active'] ?? true)];
        }

        // A binding that already has prices or orders is never dropped, only hidden: sent without it, it comes back hidden.
        foreach (self::all() as $old) {
            if (! in_array($old['key'], array_column($bindings, 'key'), true)) {
                $bindings[] = ['key' => $old['key'], 'label' => $old['label'], 'active' => false];
            }
        }

        StickerSetting::query()->updateOrCreate(['key' => self::KEY], ['value' => ['bindings' => $bindings]]);

        return self::all();
    }
}
