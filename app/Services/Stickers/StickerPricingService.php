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
            'total' => round($subtotal + $contourFee, 2),
            'warnings' => $warnings,
        ];
    }
}
