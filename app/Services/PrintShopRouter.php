<?php

namespace App\Services;

use App\Models\Address;
use App\Models\BranchPrintArea;
use App\Models\BranchProductOffering;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\CatalogProductData;
use Illuminate\Support\Collection;

/**
 * Picks the print shop branch that receives a customer's order.
 *
 * One branch makes the whole order. It qualifies only when, for EVERY product line, its offering is live and priced, its
 * shop account is approved and active, it offers every colour/size asked for, it offers every print area the customer
 * designed on (with at least one printing method there) and its largest print size on that area fits the design.
 *
 * Qualifying branches are ranked: the customer's own city first, then the cheapest estimated order, then the shortest
 * production time.
 *
 * A "need" is one order line: {product, variantIds, areas (as the studio names them), layout, quantity}; see need().
 */
class PrintShopRouter
{
    /** Print areas the studio names differently from the database (the mug has one area: "front" in the studio, "wrap" in the database). */
    private const DATABASE_AREA = ['MUG-CERAMIC' => ['front' => 'wrap']];

    /**
     * One order line, in the form the router works with.
     *
     * @param  Collection<int, int>  $variantIds  the variants (colour + size) of the line
     * @param  array<int, string>  $studioAreas  the print areas the customer designed on, as the studio names them
     * @param  array<string, mixed>|null  $layout  the studio layout ({areas: {areaId: {objects: [...]}}})
     * @return array{product: Product, variantIds: Collection<int, int>, areas: array<int, array{code: string, widthCm: ?float, heightCm: ?float}>, quantity: int}
     */
    public function need(Product $product, Collection $variantIds, array $studioAreas, ?array $layout, int $quantity = 1): array
    {
        $areas = collect($studioAreas)->unique()->map(function (string $studioId) use ($product, $layout) {
            $code = $this->databaseArea($product, $studioId);
            $size = $this->designSizeCm($layout, $studioId, $code, $product);

            return ['code' => $code, 'widthCm' => $size['width'] ?? null, 'heightCm' => $size['height'] ?? null];
        })->values()->all();

        return ['product' => $product, 'variantIds' => $variantIds->values(), 'areas' => $areas, 'quantity' => max(1, $quantity)];
    }

    /** The same need, read back from a line already in the cart. */
    public function needFromCartItem(CartItem $item): array
    {
        $options = (array) $item->selected_options;

        return $this->need(
            $item->product,
            collect($item->variant_id ? [$item->variant_id] : []),
            array_values((array) ($options['print_areas'] ?? [])),
            is_array($options['layout'] ?? null) ? $options['layout'] : null,
            (int) $item->quantity,
        );
    }

    /** The offering of the best branch for a single product (the common case of one design), or null. */
    public function choose(Product $product, Collection $variantIds, array $studioAreas, ?array $layout, ?string $city): ?BranchProductOffering
    {
        $need = $this->need($product, $variantIds, $studioAreas, $layout);

        return $this->chooseForOrder(collect([$need]), $city)['offerings'][$product->id] ?? null;
    }

    /**
     * The branch that can make every line of the order, with its offering for each product.
     *
     * @param  Collection<int, array>  $needs
     * @return array{branchId: int, offerings: array<int, BranchProductOffering>, estimate: float}|null null when no single branch can make it all
     */
    public function chooseForOrder(Collection $needs, ?string $city): ?array
    {
        return $this->rankBranches($needs, $city)->first();
    }

    /**
     * Every branch that can make the whole order, best first.
     *
     * @param  Collection<int, array>  $needs
     * @return Collection<int, array{branchId: int, offerings: array<int, BranchProductOffering>, sameCity: bool, estimate: float}>
     */
    public function rankBranches(Collection $needs, ?string $city): Collection
    {
        $productIds = $needs->map(fn (array $need) => $need['product']->id)->unique()->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        $offerings = BranchProductOffering::query()
            ->with([
                'printProviderBranch.printProvider',
                'branchOfferingVariants',
                'branchPrintAreas' => fn ($query) => $query->where('is_active', true),
                'branchPrintAreas.branchPrintCapabilities' => fn ($query) => $query->where('is_active', true),
                'branchPrintAreas.branchPrintCapabilities.branchPricingRules' => fn ($query) => $query->where('is_active', true),
            ])
            ->whereIn('product_id', $productIds)
            ->where('is_active', true)
            ->where('base_price', '>', 0)
            ->whereHas('printProviderBranch', fn ($query) => $query->where('is_active', true)
                ->whereHas('printProvider', fn ($provider) => $provider->where('approval_status', 'approved')->where('is_active', true)))
            ->get()
            ->groupBy('print_provider_branch_id');

        $wantedCity = $this->normalize($city);

        return $offerings
            ->map(function (Collection $branchOfferings, $branchId) use ($needs, $productIds, $wantedCity) {
                $byProduct = $branchOfferings->keyBy('product_id');

                if ($productIds->contains(fn ($id) => ! $byProduct->has($id))) {
                    return null; // the branch does not sell every product of the order
                }

                $estimate = 0.0;

                foreach ($needs as $need) {
                    $lineEstimate = $this->estimate($byProduct[$need['product']->id], $need);

                    if ($lineEstimate === null) {
                        return null;
                    }

                    $estimate += $lineEstimate * $need['quantity'];
                }

                $branch = $byProduct->first()->printProviderBranch;

                return [
                    'branchId' => (int) $branchId,
                    'offerings' => $byProduct->all(),
                    'sameCity' => $wantedCity !== '' && $this->normalize($branch?->city) === $wantedCity,
                    'estimate' => round($estimate, 2),
                    'days' => (int) $byProduct->max('production_time_max'),
                ];
            })
            ->filter()
            ->sortBy([
                fn ($a, $b) => (int) $b['sameCity'] <=> (int) $a['sameCity'],
                fn ($a, $b) => $a['estimate'] <=> $b['estimate'],
                fn ($a, $b) => $a['days'] <=> $b['days'],
                fn ($a, $b) => $a['branchId'] <=> $b['branchId'],
            ])
            ->values();
    }

    /** The city of the customer's default address (or their newest one); null when they have none. */
    public function customerCity(User $user): ?string
    {
        return Address::query()
            ->where('user_id', $user->id)
            ->where('is_deleted', false)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->value('city');
    }

    /**
     * Width and height (cm) of the box around everything placed on one print area. Positions and sizes in the layout are
     * fractions of the studio's print zone, whose real size is the platform's definition of that area.
     *
     * @return array{width: float, height: float}|null null when nothing is placed or the area is unknown
     */
    public function designSizeCm(?array $layout, string $studioAreaId, string $databaseArea, Product $product): ?array
    {
        $objects = $layout['areas'][$studioAreaId]['objects'] ?? [];
        $zone = collect(CatalogProductData::areaDefinitions($product->code))->firstWhere('code', $databaseArea);

        if (! is_array($objects) || $objects === [] || ! $zone) {
            return null;
        }

        $zoneWidth = $zone['width'] / 10;
        $zoneHeight = $zone['height'] / 10;
        $minX = $minY = INF;
        $maxX = $maxY = -INF;

        foreach ($objects as $object) {
            $width = $this->objectWidth($object) * $zoneWidth;
            $height = $this->objectHeight($object) * $zoneHeight;
            $angle = deg2rad((float) ($object['angle'] ?? 0));
            $halfX = (abs($width * cos($angle)) + abs($height * sin($angle))) / 2;
            $halfY = (abs($width * sin($angle)) + abs($height * cos($angle))) / 2;
            $centerX = (float) ($object['x'] ?? 0) * $zoneWidth;
            $centerY = (float) ($object['y'] ?? 0) * $zoneHeight;

            $minX = min($minX, $centerX - $halfX);
            $maxX = max($maxX, $centerX + $halfX);
            $minY = min($minY, $centerY - $halfY);
            $maxY = max($maxY, $centerY + $halfY);
        }

        return ['width' => round($maxX - $minX, 2), 'height' => round($maxY - $minY, 2)];
    }

    public function databaseArea(Product $product, string $studioAreaId): string
    {
        return self::DATABASE_AREA[strtoupper($product->code)][$studioAreaId] ?? $studioAreaId;
    }

    /** Fraction of the zone width an object covers (text is scaled as a whole). */
    private function objectWidth(array $object): float
    {
        $width = (float) ($object['width'] ?? 0);

        return ($object['kind'] ?? '') === 'text' ? $width * (float) ($object['scaleX'] ?? 1) : $width;
    }

    /** Fraction of the zone height an object covers; a text object only stores its font size, so its lines are counted. */
    private function objectHeight(array $object): float
    {
        if (($object['kind'] ?? '') !== 'text') {
            return (float) ($object['height'] ?? 0);
        }

        $lines = max(1, substr_count((string) ($object['text'] ?? ''), "\n") + 1);

        return (float) ($object['fontSize'] ?? 0) * (float) ($object['scaleY'] ?? 1) * (float) ($object['lineHeight'] ?? 1.2) * $lines;
    }

    /**
     * What this offering would charge per piece for the line, or null when it cannot make it: the base price plus, for each
     * print area, its cheapest printing method (fixed fee + price per 100 cm² of the design).
     *
     * @param  array{variantIds: Collection<int, int>, areas: array<int, array{code: string, widthCm: ?float, heightCm: ?float}>}  $need
     */
    private function estimate(BranchProductOffering $offering, array $need): ?float
    {
        $available = $offering->branchOfferingVariants->where('is_available', true)->pluck('variant_id');

        if (! $need['variantIds']->every(fn ($id) => $available->contains($id))) {
            return null;
        }

        $total = (float) $offering->base_price;

        foreach ($need['areas'] as $wanted) {
            /** @var BranchPrintArea|null $area */
            $area = $offering->branchPrintAreas->firstWhere('code', $wanted['code']);

            if (! $area) {
                return null;
            }

            if ($wanted['widthCm'] !== null && ($wanted['widthCm'] * 10 > $area->max_width_mm || $wanted['heightCm'] * 10 > $area->max_height_mm)) {
                return null;
            }

            $squareCm = ($wanted['widthCm'] ?? 0) * ($wanted['heightCm'] ?? 0);
            $cheapest = $area->branchPrintCapabilities
                ->map(function ($capability) use ($squareCm) {
                    $rules = $capability->branchPricingRules;
                    $fixed = (float) ($rules->firstWhere('pricing_type', 'print_method_addon')?->amount ?? 0);
                    $rate = (float) ($rules->firstWhere('pricing_type', 'print_area_rate')?->amount ?? 0);

                    return $fixed + $rate * $squareCm / 100;
                })
                ->min();

            if ($cheapest === null) {
                return null; // the shop offers the area but no printing method on it
            }

            $total += $cheapest;
        }

        return round($total, 2);
    }

    private function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
