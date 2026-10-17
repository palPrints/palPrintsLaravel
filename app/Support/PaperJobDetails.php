<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Describes a paper printing job in words: the lines about the whole order, and one short card per file. The print shop
 * reads them on its order and the customer in "my orders". The job is the one PaperPricing::normalizeJob() saved with the
 * order item.
 */
class PaperJobDetails
{
    private const VALUES = [
        'standard' => 'عادي 80 جم',
        'thick' => 'فاخر 120 جم',
        'coated' => 'مصقول 150 جم',
        'single' => 'وجه واحد',
        'double' => 'وجهين',
        '1' => 'صفحة واحدة لكل وجه',
        '2' => 'صفحتان لكل وجه',
        '4' => '4 صفحات لكل وجه',
        '6' => '6 صفحات لكل وجه',
        '9' => '9 صفحات لكل وجه',
    ];

    private static function bindingLabel(?string $id): string
    {
        return $id && $id !== 'none' ? (PaperPrices::BINDINGS[$id] ?? $id) : 'بدون تغليف';
    }

    private static function separate(array $job): bool
    {
        return ($job['grouping'] ?? '') === 'separate' && count($job['files']) > 1;
    }

    /**
     * The lines about the whole order: how the files are put together, copies, binding and how copies are ordered.
     *
     * @param  array<string, mixed>  $job  grouping, quantity, combined_binding, collate and files
     * @return array<int, array{label: string, value: string}>
     */
    public static function rows(array $job): array
    {
        $separate = self::separate($job);
        $rows = [['label' => 'طريقة الملفات', 'value' => $separate ? 'فصل الملفات (كل ملف مطبوع على حدة)' : 'دمج الملفات في مطبوع واحد']];

        if (! $separate) {
            $rows[] = ['label' => 'عدد النسخ', 'value' => (string) ($job['quantity'] ?? 1)];
            $rows[] = ['label' => 'التغليف', 'value' => self::bindingLabel($job['combined_binding'] ?? null)];
        }

        // How several copies are put together matters only when there is more than one.
        $copies = $separate ? collect($job['files'])->max(fn ($file) => (int) ($file['quantity'] ?? 1)) : (int) ($job['quantity'] ?? 1);
        if ($copies > 1) {
            $rows[] = ['label' => 'ترتيب النسخ', 'value' => ($job['collate'] ?? true) ? 'نسخة كاملة بعد نسخة (مرتّبة)' : 'كل صفحة بنسخها مع بعض'];
        }

        return $rows;
    }

    /**
     * One short card per file: its name and a few lines (pages, paper, printing, other choices). A line with nothing to say
     * is left out.
     *
     * @param  Collection<int, string>  $names  the original file names, in the order of the job's files
     * @return array<int, array{title: string, rows: array<int, array{label: string, value: string}>}>
     */
    public static function files(array $job, Collection $names): array
    {
        $separate = self::separate($job);
        $cards = [];

        foreach ($job['files'] as $index => $file) {
            $pages = ($file['pages'] ?? 0).' صفحة';
            if (! empty($file['range'])) {
                $pages .= ' — المطلوب طباعته: '.$file['range'].' ('.($file['printed'] ?? '').' صفحة)';
            }
            if (($file['subset'] ?? 'all') !== 'all') {
                $pages .= ' — '.($file['subset'] === 'odd' ? 'الصفحات الفردية فقط' : 'الصفحات الزوجية فقط').' ('.($file['printed'] ?? '').' صفحة)';
            }

            $printing = ! empty($file['booklet'])
                ? ['كتيّب (وجهين، صفحتان في كل وجه)']
                : array_filter([
                    self::VALUES[$file['sides'] ?? ''] ?? '',
                    ($file['sides'] ?? '') === 'double' ? 'القلب من الطرف '.(($file['flip'] ?? 'long') === 'short' ? 'القصير' : 'الطويل') : '',
                    self::VALUES[(string) ($file['layout'] ?? '')] ?? '',
                ]);

            $other = array_filter([
                ! empty($file['reverse']) ? 'ترتيب الصفحات معكوس' : '',
                ['portrait' => 'الورقة طولية', 'landscape' => 'الورقة عرضية'][$file['orientation'] ?? 'auto'] ?? '',
                ['rtl' => 'يُقرأ من اليمين (عربي)', 'ltr' => 'يُقرأ من اليسار (إنجليزي)'][$file['direction'] ?? 'auto'] ?? '',
            ]);

            $rows = [
                ['label' => 'الصفحات', 'value' => $pages],
                ['label' => 'الورق', 'value' => implode(' • ', array_filter([strtoupper((string) ($file['size'] ?? '')), self::VALUES[$file['paper'] ?? ''] ?? PaperPrices::TYPES[$file['paper'] ?? ''] ?? '', PaperPrices::INK[$file['color'] ?? ''] ?? '']))],
                ['label' => 'الطباعة', 'value' => implode(' • ', $printing)],
            ];

            if ($other !== []) {
                $rows[] = ['label' => 'ملاحظات', 'value' => implode(' • ', $other)];
            }

            if ($separate) {
                $rows[] = ['label' => 'التغليف والنسخ', 'value' => self::bindingLabel($file['binding'] ?? null).' • النسخ: '.($file['quantity'] ?? 1)];
            }

            $cards[] = ['title' => $names[$index] ?? 'ملف '.($index + 1), 'rows' => $rows];
        }

        return $cards;
    }
}
