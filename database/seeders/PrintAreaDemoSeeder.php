<?php

namespace Database\Seeders;

use App\Models\BranchPrintArea;
use App\Models\BranchProductOffering;
use Illuminate\Database\Seeder;

/**
 * Demo print areas only (no products, prices or capabilities are touched),
 * so the designer's "choose product" page has areas to pick from.
 */
class PrintAreaDemoSeeder extends Seeder
{
    public function run(): void
    {
        BranchProductOffering::query()->with('product')->get()->each(function (BranchProductOffering $offering): void {
            foreach ($this->areas(strtoupper($offering->product->code)) as $code => [$name, $width, $height]) {
                BranchPrintArea::updateOrCreate(
                    ['branch_product_offering_id' => $offering->id, 'code' => $code],
                    ['name' => $name, 'max_width_mm' => $width, 'max_height_mm' => $height, 'is_active' => true],
                );
            }
        });
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: int}>
     */
    private function areas(string $productCode): array
    {
        return match ($productCode) {
            'TSHIRT-CLASSIC' => ['front' => ['الصدر (أمام)', 280, 350], 'back' => ['الظهر', 300, 380]],
            'HOODIE-PREMIUM' => ['front' => ['الصدر (أمام)', 260, 300], 'back' => ['الظهر', 300, 360]],
            'MUG-CERAMIC' => ['wrap' => ['طباعة كاملة', 210, 90]],
            'PHONE-CASE' => ['back' => ['الوجه الخلفي', 70, 150]],
            'NOTEBOOK-CUSTOM' => ['cover' => ['الغلاف', 148, 210]],
            'CAP-CLASSIC' => ['front' => ['الواجهة', 120, 60]],
            'POSTER-PRINT' => ['front' => ['وجه البوستر', 297, 420]],
            'PAPER-PRINT' => ['front' => ['الصفحة الأمامية', 210, 297]],
            default => ['front' => ['الواجهة', 200, 200]],
        };
    }
}
