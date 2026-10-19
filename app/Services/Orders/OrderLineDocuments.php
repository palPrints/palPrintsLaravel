<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintFile;
use App\Services\PrintShopRouter;
use App\Support\CatalogProductData;
use App\Support\OrderSheetPdf;
use App\Support\PaperJobDetails;
use Illuminate\Support\Facades\Storage;

/**
 * What a print shop gets for one order line, wherever it reads it: the orders page, the files it downloads, and the
 * messages that go to its Telegram / WhatsApp. Per line: one details PDF (the order, the options in words — size,
 * colour, print areas… — the notes and the print files), and beside it the files themselves, each under a name the shop
 * can read: a design's print-ready picture per area ("أمامي.png"), or a paper job's own PDFs.
 */
class OrderLineDocuments
{
    /** Human labels for the options saved on an order item. */
    private const OPTION_LABELS = [
        'paper_size' => 'حجم الورق',
        'paper_type' => 'نوع الورق',
        'color_mode' => 'لون الطباعة',
        'sides' => 'جوانب الطباعة',
        'layout' => 'تخطيط الصفحة',
        'grouping' => 'طريقة الملفات',
        'binding' => 'التغليف',
        'printing_method_name' => 'تقنية الطباعة',
        'size' => 'القياس',
        'color' => 'اللون',
    ];

    /** Human labels for the option values saved by the cart. */
    private const OPTION_VALUES = [
        'standard' => 'عادي 80 جم',
        'thick' => 'فاخر 120 جم',
        'coated' => 'مصقول 150 جم',
        'bw' => 'أبيض وأسود',
        'color' => 'ملون',
        'single' => 'وجه واحد',
        'double' => 'وجهين',
        '1' => 'صفحة واحدة لكل وجه',
        '2' => 'صفحتان لكل وجه',
        '4' => '4 صفحات لكل وجه',
        '6' => '6 صفحات لكل وجه',
        '9' => '9 صفحات لكل وجه',
        'combined' => 'دمج الملفات',
        'separate' => 'فصل الملفات',
        'none' => 'بدون تغليف',
    ];

    /** One order line as the shop sees it: options in words, files, download links. */
    public function present(Order $order, OrderItem $item): array
    {
        $options = collect($item->selected_options ?? []);

        $details = $options
            // A studio design saves its whole layout (nested arrays) under "layout", the paper option's name: not a plain option to list.
            ->filter(fn ($value, $key) => isset(self::OPTION_LABELS[$key]) && (is_scalar($value) || (is_array($value) && collect($value)->every(fn ($item) => is_scalar($item)))) && $value !== '' && $value !== [])
            ->map(fn ($value, $key) => [
                'label' => self::OPTION_LABELS[$key],
                'value' => collect((array) $value)->map(fn ($v) => self::OPTION_VALUES[(string) $v] ?? (string) $v)->implode('، '),
            ])
            ->values();

        $areas = $options->get('print_areas');
        if (is_array($areas) && $areas !== []) {
            $details->push(['label' => 'مناطق الطباعة', 'value' => implode('، ', array_map('strval', $areas))]);
        }

        // A paper job is described file by file (each file can have its own paper, colour, binding and copies).
        $paperFiles = [];
        $job = $options->get('paper_job');
        if (is_array($job) && ! empty($job['files'])) {
            $details = $details->reject(fn (array $row) => in_array($row['label'], ['حجم الورق', 'نوع الورق', 'لون الطباعة', 'جوانب الطباعة', 'تخطيط الصفحة', 'طريقة الملفات', 'التغليف'], true))->values();
            $jobFiles = $item->printFiles->where('status', '!=', PrintFile::STATUS_DELETED)->sortBy('id')->values();
            $names = $jobFiles->pluck('original_name');

            foreach (PaperJobDetails::rows($job) as $row) {
                $details->push($row);
            }

            // Each card carries the download of its own file (the cards follow the order of the files).
            $paperFiles = collect(PaperJobDetails::files($job, $names))
                ->map(fn (array $card, int $index) => $card + ['url' => isset($jobFiles[$index]) ? route('print-provider.requests.files', [$order, $jobFiles[$index]]) : null])
                ->all();
        }

        $image = $item->design?->image;
        $files = $item->printFiles->where('status', '!=', PrintFile::STATUS_DELETED)->reject->isDraftExport();

        return [
            'name' => $item->product?->name ?? 'منتج',
            'quantity' => (int) $item->quantity,
            'cost' => (float) $item->provider_cost,
            'type' => $item->item_type === OrderItem::TYPE_CUSTOMER_UPLOAD ? 'upload' : 'design',
            'design_title' => $item->design?->title,
            'image' => $image ? (str_starts_with($image, 'http') ? $image : asset($image)) : null,
            'details' => $details->all(),
            'paper_files' => $paperFiles,
            // The studio's print-ready pictures are listed apart from what the customer uploaded.
            'print_files' => $files->filter->isPrintReady()
                ->map(fn (PrintFile $file) => [
                    'name' => $file->areaLabel(),
                    'size' => $file->file_size,
                    'url' => route('print-provider.requests.files', [$order, $file]),
                ])->values()->all(),
            'files' => $files->reject->isPrintReady()
                ->map(fn (PrintFile $file) => [
                    'name' => $file->original_name,
                    'size' => $file->file_size,
                    'pages' => $file->page_count,
                    'url' => route('print-provider.requests.files', [$order, $file]),
                ])->values()->all(),
            'package_url' => $files->isNotEmpty() ? route('print-provider.requests.package', [$order, $item]) : null,
            'sheet_url' => route('print-provider.requests.sheet', [$order, $item]),
            'order_number' => $order->order_number,
            'date_label' => $order->reachedShopAt()->translatedFormat('j F Y'),
            'notes' => $order->notes,
        ];
    }

    /** The files of a line that can be handed to the shop (not deleted, not a leftover draft, present on the disk). */
    public function lineFiles(OrderItem $item)
    {
        $disk = Storage::disk('local');

        return $item->printFiles->where('status', '!=', PrintFile::STATUS_DELETED)->reject->isDraftExport()
            ->filter(fn (PrintFile $file) => ($file->disk ?: 'local') === 'local' && $disk->exists($file->stored_path));
    }

    /** The options a shop needs at a glance, on one line: "القياس: L · اللون: كحلي · مناطق الطباعة: أمامي". */
    public function keyDetails(Order $order, OrderItem $item): string
    {
        $wanted = ['القياس', 'اللون', 'مناطق الطباعة', 'تقنية الطباعة', 'حجم الورق', 'لون الطباعة'];

        return collect($this->present($order, $item)['details'])
            ->filter(fn (array $row) => in_array($row['label'], $wanted, true))
            ->map(fn (array $row) => $row['label'].': '.$row['value'])
            ->implode(' · ');
    }

    /** A paper line is described by the customer's own files; anything else (a garment, a mug…) carries a design. */
    public function isPaper(OrderItem $item): bool
    {
        return ! empty(data_get($item->selected_options, 'paper_job.files'));
    }

    /**
     * The files that go to the shop beside the details PDF, each under the name it downloads as: a design line's
     * print-ready pictures, one per area ("أمامي.png"), or its originals when it has none; a paper line's own files.
     *
     * @return \Illuminate\Support\Collection<int, PrintFile>
     */
    public function filesToSend(OrderItem $item)
    {
        $files = $this->lineFiles($item)->sortBy('id')->values();

        return $this->isPaper($item) || ! $files->contains->isPrintReady() ? $files : $files->filter->isPrintReady()->values();
    }

    /** The details PDF: the order, the options in words, the notes and the print files (text only, no GD needed). */
    public function detailsPdf(Order $order, OrderItem $item): string
    {
        return (new OrderSheetPdf)->details($this->sheetData($order, $item, $this->areas($item)));
    }

    /** The details PDF's file name, e.g. "PLP-20261019-RCQHTF-1-details.pdf" (1 = the line's place in the order). */
    public function fileName(Order $order, OrderItem $item): string
    {
        $line = $order->items()->orderBy('id')->pluck('id')->search($item->id);

        return $order->order_number.'-'.(($line === false ? 0 : $line) + 1).'-details.pdf';
    }

    /** The print-ready pictures of a line, one row per print area, for the files table of the details PDF. */
    private function areas(OrderItem $item): array
    {
        $sizes = $this->designSizes($item);

        return $this->lineFiles($item)->filter->isPrintReady()->sortBy('id')->map(fn (PrintFile $file) => [
            'name' => $file->areaLabel(),
            'file' => $file->original_name,
            'dimensions' => $sizes[$file->areaSlug()] ?? null,
            'size' => 0,
            'src' => '',
        ])->values()->all();
    }

    /**
     * How big the customer's design is on the product, per studio area, in centimetres ("11.6 × 10"): the box around all
     * its elements. The studio keeps sizes and positions as fractions of the print area.
     *
     * @return array<string, string>
     */
    private function designSizes(OrderItem $item): array
    {
        $product = $item->product;
        $router = app(PrintShopRouter::class);
        $definitions = $product ? collect(CatalogProductData::areaDefinitions($product->code)) : collect();
        $sizes = [];

        foreach ((array) data_get($item->selected_options, 'layout.areas', []) as $studioId => $area) {
            $zone = $product ? $definitions->firstWhere('code', $router->databaseArea($product, (string) $studioId)) : null;
            $objects = (array) ($area['objects'] ?? []);

            if (! $zone || $objects === []) {
                continue;
            }

            $edges = [];

            foreach ($objects as $object) {
                $isText = ($object['kind'] ?? '') === 'text';
                $width = (float) ($object['width'] ?? 0) * ($isText ? (float) ($object['scaleX'] ?? 1) : 1);
                $lines = max(1, substr_count((string) ($object['text'] ?? ''), "\n") + 1);
                $height = $isText
                    ? (float) ($object['fontSize'] ?? 0) * (float) ($object['scaleY'] ?? 1) * (float) ($object['lineHeight'] ?? 1.2) * $lines
                    : (float) ($object['height'] ?? 0);
                $edges[] = [(float) ($object['x'] ?? 0) - $width / 2, (float) ($object['x'] ?? 0) + $width / 2, (float) ($object['y'] ?? 0) - $height / 2, (float) ($object['y'] ?? 0) + $height / 2];
            }

            $edges = collect($edges);
            $width = ($edges->max(1) - $edges->min(0)) * $zone['width'] / 10;
            $height = ($edges->max(3) - $edges->min(2)) * $zone['height'] / 10;
            $sizes[strtolower((string) $studioId)] = round($width, 1).' × '.round($height, 1);
        }

        return $sizes;
    }

    /** @param  array<int, array{name: string, size: int, src: string}>  $areas  the print-ready pictures of the line, by area name */
    private function sheetData(Order $order, OrderItem $item, array $areas): array
    {
        $presented = $this->present($order, $item);

        $fonts = collect(data_get($item->selected_options, 'layout.areas', []))
            ->flatMap(fn ($area) => collect($area['objects'] ?? [])->where('kind', 'text')->pluck('fontFamily'))
            ->filter()->unique()->values()->all();

        return [
            'order' => $order,
            'item' => $presented,
            'fonts' => $fonts,
            'packageUrl' => $presented['package_url'],
            'areas' => $areas,
        ];
    }
}
