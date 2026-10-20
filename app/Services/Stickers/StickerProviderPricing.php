<?php

namespace App\Services\Stickers;

use App\Models\BranchProductOffering;
use App\Models\OrderItem;
use Illuminate\Validation\ValidationException;

class StickerProviderPricing
{
    public function quote(BranchProductOffering $offering, array $options): array
    {
        $pages = $options['layout_snapshot']['pages'] ?? [];
        if (! is_array($pages) || $pages === [] || collect($pages)->contains(fn ($page) => ! is_array($page) || empty($page['items']))) {
            throw ValidationException::withMessages(['cart' => 'توزيع صفحات الملصقات غير مكتمل.']);
        }
        $materials = $offering->sticker_materials ?: ['paper' => [
            ['key' => 'photo-sticker', 'label' => 'فوتو لاصق', 'available' => true, 'price_per_sheet' => 0],
            ['key' => 'photo-non-adhesive', 'label' => 'فوتو غير لاصق', 'available' => true, 'price_per_sheet' => 0],
        ], 'protection' => ['label' => 'طبقة حماية', 'available' => false, 'price_per_sheet' => 0]];
        $paper = collect($materials['paper'] ?? [])->keyBy('key');
        $base = (float) $offering->base_price;
        $total = 0.0; $rows = [];
        foreach ($pages as $index => $page) {
            $choice = $options['page_specs'][$index] ?? [];
            $paperRow = $paper->get($choice['paper_key'] ?? 'photo-sticker') ?: $paper->first();
            if (! $paperRow || ! ($paperRow['available'] ?? false)) throw ValidationException::withMessages(['cart' => 'نوع ورق غير متاح لدى المطبعة.']);
            $protection = (bool) ($choice['protection'] ?? false);
            if ($protection && ! ($materials['protection']['available'] ?? false)) throw ValidationException::withMessages(['cart' => 'طبقة الحماية غير متاحة لدى المطبعة.']);
            $amount = $base + (float) $paperRow['price_per_sheet'] + ($protection ? (float) ($materials['protection']['price_per_sheet'] ?? 0) : 0);
            $total += $amount;
            $rows[] = ['page' => $index + 1, 'paper' => $paperRow['label'], 'protection' => $protection, 'total' => round($amount, 2)];
        }
        return ['unit' => 'a4_sheet', 'sheets' => count($pages), 'unit_price' => $base, 'materials' => $rows, 'total' => round($total, 2)];
    }

    public function assignment(OrderItem $item, BranchProductOffering $offering): array
    {
        if ($item->item_type !== OrderItem::TYPE_STICKER_BUNDLE) {
            return [];
        }
        $options = $item->selected_options ?? [];
        $quote = $this->quote($offering, $options);

        return ['provider_cost' => $quote['total'],
            'platform_commission' => max(0, (float) $item->total_price - $quote['total']),
            'selected_options' => array_merge($options, ['provider_pricing' => $quote])];
    }
}
