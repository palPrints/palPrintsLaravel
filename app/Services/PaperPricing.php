<?php

namespace App\Services;

use App\Models\BranchProductOffering;
use App\Models\Variant;
use App\Support\PaperPrices;
use Illuminate\Validation\ValidationException;

/**
 * Prices a paper printing job from what one print shop charges (see PaperPrices): the blank sheets, the printing of every
 * page (which depends on the paper type and the colour) and the binding. Nothing here is a fixed platform price, so a shop that does not sell a paper type, colour, size or
 * binding simply cannot make a job that needs it.
 *
 * A job (see normalizeJob) is what the customer chose: how the files are grouped, the quantity, and per file its pages,
 * size, paper, colour, sides, pages per sheet and binding.
 */
class PaperPricing
{
    public const SIDES = ['single', 'double'];

    public const LAYOUTS = [1, 2, 4, 6, 9];

    public const MAX_FILES = 10;

    public const MAX_PAGES = 500;

    public const MAX_QUANTITY = 1000;

    /** @var array<int, array<int, string>> */
    private array $sizes = [];

    /**
     * The size code (a4, a5...) of each variant of the paper product.
     *
     * @return array<int, string> variant id => size code
     */
    public function sizeByVariant(int $productId): array
    {
        return $this->sizes[$productId] ??= Variant::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->with('values.productAttributeValue.attributeValue', 'values.productAttributeValue.productAttribute.attribute')
            ->get()
            ->mapWithKeys(function (Variant $variant) {
                $size = $variant->values
                    ->map(fn ($value) => $value->productAttributeValue)
                    ->first(fn ($value) => $value?->productAttribute?->attribute?->code === 'size')
                    ?->attributeValue?->code;

                return $size ? [$variant->id => strtolower($size)] : [];
            })->all();
    }

    /**
     * What a shop's paper offering sells right now.
     *
     * @return array{sheets: array<string, array<string, float>>, ink: array<string, float>, bindings: array<string, float>}
     */
    public function priceList(BranchProductOffering $offering): array
    {
        return PaperPrices::priceList($offering, $this->sizeByVariant($offering->product_id));
    }

    /**
     * What this shop would charge for the job, or null when it cannot make it (a size, paper type, colour or binding it does
     * not sell).
     *
     * @param  array<string, mixed>  $job  see normalizeJob()
     * @return array{lines: array<int, array{amount: float, quantity: int}>, printing: float, binding: float, total: float}|null
     */
    public function quote(BranchProductOffering $offering, array $job): ?array
    {
        $list = $this->priceList($offering);
        $separate = $job['grouping'] === 'separate' && count($job['files']) > 1;
        $lines = [];
        $bindingCost = 0.0;

        foreach ($job['files'] as $file) {
            $ink = $list['ink'][$file['paper']][$file['color']] ?? null; // the printed price depends on the paper it is printed on

            if ($ink === null) {
                return null;
            }

            // The shop prices a printed sheet: double sided and several pages per sheet mean fewer sheets, and only the pages
            // in the chosen range are printed. A booklet is two pages a side, both sides, so four pages to a sheet.
            $printed = (int) ($file['printed'] ?? $file['pages']);
            $sheets = ! empty($file['booklet'])
                ? (int) ceil($printed / 4)
                : (int) ceil($printed / ($file['layout'] * ($file['sides'] === 'double' ? 2 : 1)));
            $quantity = $separate ? $file['quantity'] : $job['quantity'];
            $lines[] = ['amount' => round($sheets * $ink * $quantity, 2), 'quantity' => $quantity];

            if ($job['grouping'] === 'separate') {
                $price = $this->bindingPrice($list, $file['binding']);

                if ($price === null) {
                    return null;
                }

                $bindingCost += $price * $quantity;
            }
        }

        if ($job['grouping'] === 'combined') {
            $price = $this->bindingPrice($list, $job['combined_binding']);

            if ($price === null) {
                return null;
            }

            $bindingCost = $price * $job['quantity'];
        }

        // The customer always pays a whole number: each part is rounded up to the next shekel.
        $printing = ceil(round(max(array_sum(array_column($lines, 'amount')), (float) $offering->base_price), 2));
        $binding = ceil(round($bindingCost, 2));

        return [
            'lines' => $lines,
            'printing' => $printing,
            'binding' => $binding,
            'total' => $printing + $binding,
        ];
    }

    /**
     * What the customer sent, checked and cleaned. Anything outside what the platform sells is refused.
     *
     * @param  array<string, mixed>  $input  grouping, quantity, combined_binding and files (pages, size, paper, color, sides, layout, binding, quantity)
     * @return array{grouping: string, quantity: int, combined_binding: ?string, files: array<int, array<string, mixed>>, variant_ids: array<int, int>}
     */
    public function normalizeJob(array $input, int $productId): array
    {
        $sizes = $this->sizeByVariant($productId);
        $fail = fn (string $message) => throw ValidationException::withMessages(['job' => $message]);
        $files = array_values((array) ($input['files'] ?? []));

        if ($files === [] || count($files) > self::MAX_FILES) {
            $fail('أضف من ملف إلى '.self::MAX_FILES.' ملفات.');
        }

        $grouping = ($input['grouping'] ?? '') === 'separate' ? 'separate' : 'combined';
        $bindingIds = array_merge(array_keys(PaperPrices::BINDINGS), ['none']);
        $binding = fn ($value) => in_array($value, $bindingIds, true) ? $value : null;
        $pagesTotal = 0;
        $clean = [];

        foreach ($files as $file) {
            $file = (array) $file;
            $size = strtolower((string) ($file['size'] ?? ''));
            $pages = (int) ($file['pages'] ?? 0);
            $pagesTotal += $pages;

            if (! in_array($size, $sizes, true)
                || ! array_key_exists((string) ($file['paper'] ?? ''), PaperPrices::TYPES)
                || ! array_key_exists((string) ($file['color'] ?? ''), PaperPrices::INK)
                || ! in_array($file['sides'] ?? '', self::SIDES, true)
                || ! in_array((int) ($file['layout'] ?? 0), self::LAYOUTS, true)
                || $pages < 1 || $pages > self::MAX_PAGES) {
                $fail('خصائص الطباعة غير صحيحة. أعد اختيار حجم الورق ونوعه ولونه وجوانب الطباعة.');
            }

            // Which pages are printed: all of them, or a range such as "1-9, 12" that must lie inside the file.
            $rangeText = trim((string) ($file['range'] ?? ''));
            $pageNumbers = range(1, $pages);
            if ($rangeText !== '') {
                $parsed = self::parseRange($rangeText, $pages);
                if ($parsed === null) {
                    $fail('نطاق الصفحات غير صحيح. اكتبيه مثل: 1-9, 12 وضمن صفحات الملف.');
                }
                $pageNumbers = $parsed['pages'];
                $rangeText = $parsed['text'];
            }

            // Only the odd or only the even pages of that range (the shop turns the paper over by hand).
            $subset = in_array($file['subset'] ?? 'all', ['odd', 'even'], true) ? $file['subset'] : 'all';
            if ($subset !== 'all') {
                $pageNumbers = array_values(array_filter($pageNumbers, fn (int $page) => ($page % 2 === 1) === ($subset === 'odd')));
            }

            if ($pageNumbers === []) {
                $fail('لا توجد صفحات '.($subset === 'odd' ? 'فردية' : 'زوجية').' ضمن النطاق الذي كتبتيه.');
            }

            $printed = count($pageNumbers);

            // A booklet is printed on both sides, two pages a side, so it decides those two choices.
            $booklet = filter_var($file['booklet'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $clean[] = [
                'pages' => $pages, 'range' => $rangeText, 'subset' => $subset, 'reverse' => filter_var($file['reverse'] ?? false, FILTER_VALIDATE_BOOLEAN), 'printed' => $printed, 'size' => $size, 'paper' => $file['paper'], 'color' => $file['color'],
                'sides' => $booklet ? 'double' : $file['sides'], 'layout' => $booklet ? 2 : (int) $file['layout'],
                'booklet' => $booklet, 'flip' => ($file['flip'] ?? 'long') === 'short' ? 'short' : 'long',
                'orientation' => in_array($file['orientation'] ?? 'auto', ['portrait', 'landscape'], true) ? $file['orientation'] : 'auto',
                'direction' => in_array($file['direction'] ?? 'auto', ['rtl', 'ltr'], true) ? $file['direction'] : 'auto',
                'binding' => $binding($file['binding'] ?? null),
                'quantity' => max(1, min(self::MAX_QUANTITY, (int) ($file['quantity'] ?? 1))),
            ];
        }

        if ($pagesTotal > self::MAX_PAGES) {
            $fail('الحد الأقصى '.self::MAX_PAGES.' صفحة للطلب.');
        }

        $job = [
            'grouping' => $grouping,
            'quantity' => max(1, min(self::MAX_QUANTITY, (int) ($input['quantity'] ?? 1))),
            'combined_binding' => $binding($input['combined_binding'] ?? null),
            // With several copies: whole copies one after the other (true), or every page with all its copies together.
            'collate' => filter_var($input['collate'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'files' => $clean,
        ];

        if ($grouping === 'combined' && $job['combined_binding'] === null) {
            $fail('اختر نوع التغليف.');
        }

        if ($grouping === 'separate' && collect($clean)->contains(fn (array $file) => $file['binding'] === null)) {
            $fail('اختر تغليفًا لكل ملف.');
        }

        $variantsBySize = array_flip(array_reverse($sizes, true));
        $job['variant_ids'] = collect($clean)->pluck('size')->unique()->map(fn (string $size) => (int) $variantsBySize[$size])->values()->all();

        return $job;
    }

    /**
     * A page range such as "1-9, 12" checked against the number of pages of the file.
     *
     * @return array{text: string, count: int, pages: array<int, int>}|null  the range written in a tidy way, how many different pages it holds and which; null when it is not valid
     */
    public static function parseRange(string $range, int $pages): ?array
    {
        $range = strtr($range, ['،' => ',', '؛' => ',', '–' => '-', '—' => '-', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $seen = [];
        $parts = [];

        foreach (explode(',', $range) as $token) {
            $token = trim($token);

            if (! preg_match('/^(\d{1,4})(?:\s*-\s*(\d{1,4}))?$/', $token, $match)) {
                return null;
            }

            $from = (int) $match[1];
            $to = (int) ($match[2] ?? $match[1]);

            if ($from < 1 || $to < $from || $to > $pages) {
                return null;
            }

            foreach (range($from, $to) as $page) {
                $seen[$page] = true;
            }

            $parts[] = $from === $to ? (string) $from : $from.'-'.$to;
        }

        ksort($seen);

        return ['text' => implode(', ', $parts), 'count' => count($seen), 'pages' => array_keys($seen)];
    }

    /** The price of a binding; "no binding" is free; null when the shop does not sell it. */
    private function bindingPrice(array $list, ?string $binding): ?float
    {
        if ($binding === null || $binding === 'none') {
            return 0.0;
        }

        return $list['bindings'][$binding] ?? null;
    }
}
