<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PrintProviderBranch;

/**
 * The print shops the admin can send an order to: when approving a payment, and when a shop turned the order down.
 * "top" are the best three that can make the whole order (the same ranking the cart uses: the customer's city first, then
 * the lowest estimated cost, then the shortest production time), each with the reasons it stands out. "others" are the
 * remaining shops that sell every product but do not match every detail (a colour, a size or a print area), kept so the
 * admin can still choose one by hand.
 */
class OrderShopChoices
{
    /**
     * Active shops that offer every product of the order, except the ones named in $excludedBranchIds
     * (shops that already turned this order down).
     *
     * @param  array<int, int>  $excludedBranchIds
     * @return \Illuminate\Support\Collection<int, PrintProviderBranch>
     */
    public function eligibleBranches(Order $order, array $excludedBranchIds = [])
    {
        $productIds = $order->items->pluck('product_id')->unique()->values();
        if ($productIds->isEmpty()) {
            return collect();
        }

        return PrintProviderBranch::query()
            ->with(['printProvider:id,company_name,user_id', 'branchProductOfferings' => fn ($query) => $query->where('is_active', true)->whereIn('product_id', $productIds)])
            ->whereNotIn('id', $excludedBranchIds)
            ->where('is_active', true)
            ->whereHas('printProvider', fn ($query) => $query->where('is_active', true)->where('approval_status', 'approved'))
            ->whereHas('branchProductOfferings', fn ($query) => $query->where('is_active', true)->whereIn('product_id', $productIds), '>=', $productIds->count())
            ->get();
    }

    public function label(PrintProviderBranch $branch): string
    {
        $city = $branch->city && $branch->city !== 'غير محدد' ? ' ('.$branch->city.')' : '';

        return ($branch->printProvider?->company_name ?? 'مطبعة').$city;
    }

    /**
     * @param  array<int, int>  $excludedBranchIds
     * @return array{top: array<int, array<string, mixed>>, others: array<int, array<string, mixed>>}
     */
    public function choices(Order $order, array $excludedBranchIds = []): array
    {
        $eligible = $this->eligibleBranches($order, $excludedBranchIds)->keyBy('id');
        $router = app(PrintShopRouter::class);

        $needs = $order->items->filter(fn ($item) => $item->product)->map(fn ($item) => $router->needFromOrderItem($item))->values();
        $city = $order->shipping_address_snapshot['city'] ?? ($order->user ? $router->customerCity($order->user) : null);
        $ranked = $needs->isEmpty() ? collect() : $router->rankBranches($needs, $city)->filter(fn (array $row) => $eligible->has($row['branchId']))->values();

        $cheapest = $ranked->min('estimate');
        $fastest = $ranked->min('days');

        $top = $ranked->take(3)->values()->map(function (array $row, int $index) use ($eligible, $cheapest, $fastest, $ranked) {
            $branch = $eligible[$row['branchId']];
            $reasons = [];
            if ($row['sameCity']) {
                $reasons[] = 'في مدينة العميل';
            }
            if ($ranked->count() > 1 && $row['estimate'] === $cheapest) {
                $reasons[] = 'الأقل تكلفة';
            }
            if ($ranked->count() > 1 && $row['days'] > 0 && $row['days'] === $fastest) {
                $reasons[] = 'الأسرع تنفيذًا';
            }

            return [
                'id' => $branch->id,
                'rank' => $index + 1,
                'name' => $branch->printProvider?->company_name ?? 'مطبعة',
                'city' => $branch->city && $branch->city !== 'غير محدد' ? $branch->city : null,
                'estimate' => $row['estimate'],
                'days' => $row['days'],
                'reasons' => $reasons,
            ];
        })->all();

        $topIds = collect($top)->pluck('id');
        $others = $eligible->reject(fn (PrintProviderBranch $branch) => $topIds->contains($branch->id))
            ->map(fn (PrintProviderBranch $branch) => ['id' => $branch->id, 'label' => $this->label($branch)])
            ->values()->all();

        return ['top' => $top, 'others' => $others];
    }
}
