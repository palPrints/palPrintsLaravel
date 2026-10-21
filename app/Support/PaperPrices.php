<?php

namespace App\Support;

use App\Models\BranchOfferingVariant;
use App\Models\BranchPricingRule;
use App\Models\BranchProductOffering;
use Illuminate\Support\Collection;

/**
 * What a print shop sells as paper printing and for how much, all kept as pricing rules of its paper offering. A shop
 * prices the way a print shop talks: "every 100 sheets cost 15 ₪", "every 10 pages cost 5 ₪". Such a price is a rule whose
 * min_quantity is that number and whose amount is its price, so one sheet or page costs amount / min_quantity.
 *
 *  - paper_sheet_<type>        the blank paper of a type, per paper size (the rule sits on that size's offering variant)
 *  - paper_ink_<type>_<mode>   printing on a paper type, black and white or colour (the ink differs with the paper)
 *  - paper_binding_<id>        one binding (spiral, staples...); "no binding" is always free
 *
 * A type, a colour or a binding is on offer while it has a price. Changing a price closes the old rule and opens a new
 * one, so the earlier prices stay on record, and the last price stays valid until the shop sets another.
 */
class PaperPrices
{
    public const PRODUCT = 'PAPER-PRINT';

    /** The print colours. */
    public const INK = ['bw' => 'أبيض وأسود', 'color' => 'ملوّن'];

    /**
     * What the products page shows: every paper type, colour and binding, on or off, with the shop's current prices.
     * A pack price is {count, amount, since}: "every <count> cost <amount>".
     *
     * @param  array<int, string>  $sizeByVariant  size code of each variant id of the product
     * @param  array<int, string>  $sizeCodes  the paper sizes of the product
     * @return array{types: array<int, array<string, mixed>>, bindings: array<int, array<string, mixed>>}
     */
    public static function overview(BranchProductOffering $offering, array $sizeByVariant, array $sizeCodes): array
    {
        $anchors = self::anchors($offering, $sizeByVariant);
        $current = self::current($offering);
        $pack = fn (?BranchPricingRule $rule) => $rule
            ? ['count' => max(1, (int) $rule->min_quantity), 'amount' => (float) $rule->amount, 'since' => $rule->valid_from?->diffForHumans()]
            : null;

        // The admin's list: what is on offer now, plus any hidden type this shop still sells (so it can see and switch it off).
        $active = PaperTypes::active();
        $types = collect(PaperTypes::labels())->map(function (string $label, string $type) use ($current, $pack) {
            $ink = collect(self::INK)->map(fn (string $mode, string $key) => $pack($current->get(self::key('paper_ink_'.$type.'_'.$key, null))))->all();

            return ['key' => $type, 'label' => $label, 'enabled' => collect($ink)->filter()->isNotEmpty(), 'ink' => $ink];
        })->filter(fn (array $type) => isset($active[$type['key']]) || $type['enabled'])->values()->all();

        // The admin's list again: what is on offer now, plus any hidden binding this shop still sells (so it can see and switch it off).
        $activeBindings = PaperBindings::active();
        $bindings = collect(PaperBindings::labels())->map(function (string $label, string $id) use ($current, $pack) {
            $price = $pack($current->get(self::key('paper_binding_'.$id, null)));

            return ['key' => $id, 'label' => $label, 'enabled' => $price !== null, 'price' => $price];
        })->filter(fn (array $binding) => isset($activeBindings[$binding['key']]) || $binding['enabled'])->values()->all();

        return ['types' => $types, 'bindings' => $bindings];
    }

    /**
     * Saves the shop's paper. A price that changed gets a new rule, one that did not change is left alone, and a type, size,
     * colour or binding that is off stops being sold.
     *
     * @param  array<int, string>  $sizeByVariant  size code of each variant id of the product
     * @param  array{types: array<int, string>, prices: array<string, array<string, array{count: int|string, price: float|string}>>, ink: array<string, array<string, array{count: int|string, price: float|string}>>, bindings: array<string, float|string>}  $paper
     *                                                                                                                                                                                                                                                                                       the types that are on, with a pack price per size and per colour, and the bindings that are on with a price
     * @param  array<int, string>  $sizeCodes  the size codes the shop sells
     */
    public static function save(BranchProductOffering $offering, array $sizeByVariant, array $paper, array $sizeCodes): void
    {
        $anchors = self::anchors($offering, $sizeByVariant);
        $current = self::current($offering);
        $active = PaperTypes::active();
        $now = now();

        $write = function (string $type, ?int $variantId, ?array $pack) use ($offering, $current, $now) {
            $rule = $current->get(self::key($type, $variantId));

            if ($rule && $pack !== null && (float) $rule->amount === $pack['price'] && (int) $rule->min_quantity === $pack['count']) {
                return;
            }

            if ($rule) {
                $rule->update(['valid_until' => $now, 'is_active' => false]);
            }

            if ($pack !== null) {
                BranchPricingRule::create([
                    'branch_product_offering_id' => $offering->id,
                    'branch_offering_variant_id' => $variantId,
                    'pricing_type' => $type,
                    'value_type' => 'fixed',
                    'min_quantity' => $pack['count'],
                    'amount' => $pack['price'],
                    'is_active' => true,
                    'valid_from' => $now,
                ]);
            }
        };

        $packOf = fn (?array $entry) => $entry === null ? null : ['count' => max(1, (int) $entry['count']), 'price' => round((float) $entry['price'], 2)];

        foreach (array_keys(PaperTypes::labels()) as $type) {
            // A type the admin has hidden can no longer be switched on; a shop that already sells it may keep or drop it.
            $sold = collect(self::INK)->keys()->contains(fn ($mode) => $current->has(self::key('paper_ink_'.$type.'_'.$mode, null)));
            $on = in_array($type, $paper['types'], true) && (isset($active[$type]) || $sold);

            // The blank paper is no longer priced: close any sheet price saved earlier.
            foreach ($anchors as $anchor) {
                $write('paper_sheet_'.$type, $anchor->id, null);
            }

            foreach (array_keys(self::INK) as $mode) {
                $write('paper_ink_'.$type.'_'.$mode, null, $on ? $packOf($paper['ink'][$type][$mode] ?? null) : null);
            }
        }

        $activeBindings = PaperBindings::active();
        foreach (array_keys(PaperBindings::labels()) as $binding) {
            $price = $paper['bindings'][$binding] ?? null;

            // A binding the admin has hidden can no longer be switched on; a shop that already sells it may keep or drop it.
            if ($price !== null && ! isset($activeBindings[$binding]) && ! $current->has(self::key('paper_binding_'.$binding, null))) {
                $price = null;
            }

            $write('paper_binding_'.$binding, null, $price === null ? null : ['count' => 1, 'price' => round((float) $price, 2)]);
        }
    }

    /**
     * What one sheet, one printed page and one binding cost at this shop right now.
     *
     * @param  array<int, string>  $sizeByVariant  size code (lower case) of each variant id of the product
     * @return array{sheets: array<string, array<string, float>>, ink: array<string, array<string, float>>, bindings: array<string, float>}
     */
    public static function priceList(BranchProductOffering $offering, array $sizeByVariant): array
    {
        $sizeOfRule = [];
        foreach ($offering->branchOfferingVariants as $variant) {
            if (isset($sizeByVariant[$variant->variant_id])) {
                $sizeOfRule[$variant->id] = $sizeByVariant[$variant->variant_id];
            }
        }

        $list = ['ink' => [], 'bindings' => []];

        foreach (self::current($offering) as $rule) {
            $unit = (float) $rule->amount / max(1, (int) $rule->min_quantity);
            $type = $rule->pricing_type;

            if (preg_match('/^paper_ink_([a-z0-9]+)_([a-z]+)$/', $type, $match)) {
                $list['ink'][$match[1]][$match[2]] = $unit;
            } elseif (str_starts_with($type, 'paper_binding_')) {
                $list['bindings'][substr($type, strlen('paper_binding_'))] = $unit;
            }
        }

        return $list;
    }

    private static function key(string $type, ?int $variantId): string
    {
        return $type.'|'.($variantId ?? '');
    }

    /**
     * One offering variant per size code carries that size's prices (the oldest one, so it never changes).
     *
     * @param  array<int, string>  $sizeByVariant
     * @return array<string, BranchOfferingVariant>
     */
    private static function anchors(BranchProductOffering $offering, array $sizeByVariant): array
    {
        $anchors = [];

        foreach ($offering->branchOfferingVariants()->orderBy('id')->get() as $variant) {
            $code = $sizeByVariant[$variant->variant_id] ?? null;
            if ($code !== null && ! isset($anchors[$code])) {
                $anchors[$code] = $variant;
            }
        }

        return $anchors;
    }

    /**
     * The prices valid now (a closed rule has the date it ended on), keyed by type and variant.
     *
     * @return Collection<string, BranchPricingRule>
     */
    private static function current(BranchProductOffering $offering): Collection
    {
        return BranchPricingRule::query()
            ->where('branch_product_offering_id', $offering->id)
            ->where('pricing_type', 'like', 'paper_%')
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->get()
            ->keyBy(fn (BranchPricingRule $rule) => self::key($rule->pricing_type, $rule->branch_offering_variant_id));
    }
}
