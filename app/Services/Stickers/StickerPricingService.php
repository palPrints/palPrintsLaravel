<?php

namespace App\Services\Stickers;

class StickerPricingService
{
    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $settings
     * @return array{quantity: int, subtotal: float, contour_fee: float, total: float, warnings: array<int, string>}
     */
    public function quote(array $lines, array $settings): array
    {
        $pricing = $settings['pricing'];
        $quantity = collect($lines)->sum(fn (array $line) => max(0, (int) ($line['quantity'] ?? 0)));
        $min = (int) $pricing['min_quantity'];
        $baseQuantity = (int) $pricing['base_quantity'];
        $basePrice = (float) $pricing['base_price'];
        $extraPrice = (float) $pricing['extra_unit_price'];
        $contourDesigns = collect($lines)
            ->filter(fn (array $line) => in_array((string) ($line['cut_type'] ?? 'circle'), ['contour', 'free', 'custom'], true))
            ->count();
        $contourFee = $contourDesigns > 0
            ? ceil($contourDesigns / max(1, (int) $pricing['contour_group_size'])) * (float) $pricing['contour_group_price']
            : 0.0;
        $subtotal = $quantity <= 0 ? 0.0 : $basePrice + max(0, $quantity - $baseQuantity) * $extraPrice;
        $materialFee = 0.0;
        $materialRows = [];
        $materials = $settings['materials'] ?? [];
        foreach (($settings['page_specs'] ?? []) as $index => $spec) {
            $paper = collect($materials['paper'] ?? [])->first(fn (array $row) => ($row['key'] ?? null) === ($spec['paper_key'] ?? null) && ($row['available'] ?? false));
            if (! $paper) {
                throw \Illuminate\Validation\ValidationException::withMessages(['page_specs' => 'نوع الورق المختار غير متاح.']);
            }
            $protection = (bool) ($spec['protection'] ?? false);
            if ($protection && ! ($materials['protection']['available'] ?? false)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['page_specs' => 'طبقة الحماية غير متاحة.']);
            }
            $fee = (float) ($paper['price_per_sheet'] ?? 0) + ($protection ? (float) ($materials['protection']['price_per_sheet'] ?? 0) : 0);
            $materialFee += $fee;
            $materialRows[] = ['page' => $index + 1, 'paper' => $paper['label'] ?? $paper['key'], 'protection' => $protection, 'fee' => round($fee, 2)];
        }
        $warnings = [];

        if ($quantity > (int) $pricing['warning_quantity']) {
            $warnings[] = 'large_quantity';
        }

        if ($quantity > 0 && $quantity < $min) {
            $warnings[] = 'below_minimum';
        }

        return [
            'quantity' => (int) $quantity,
            'subtotal' => round($subtotal, 2),
            'contour_fee' => round($contourFee, 2),
            'material_fee' => round($materialFee, 2),
            'materials' => $materialRows,
            'total' => round($subtotal + $contourFee + $materialFee, 2),
            'warnings' => $warnings,
        ];
    }
}
