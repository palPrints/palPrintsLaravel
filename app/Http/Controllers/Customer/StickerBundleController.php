<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BranchProductOffering;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\StickerBundleLine;
use App\Models\StickerLibraryItem;
use App\Models\Variant;
use App\Services\Stickers\StickerLayoutService;
use App\Services\Stickers\StickerPricingService;
use App\Services\Stickers\StickerSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StickerBundleController extends Controller
{
    public function libraryImage(StickerLibraryItem $stickerLibraryItem): BinaryFileResponse
    {
        abort_unless(request()->user()?->hasRole('admin') || ($stickerLibraryItem->is_active && $stickerLibraryItem->category?->is_active), 404);

        $disk = Storage::disk($stickerLibraryItem->disk ?: 'public');
        abort_unless($disk->exists($stickerLibraryItem->image_path), 404);

        return response()->file($disk->path($stickerLibraryItem->image_path), [
            'Content-Type' => $stickerLibraryItem->mime_type ?: ($disk->mimeType($stickerLibraryItem->image_path) ?: 'image/png'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function preview(Request $request, StickerSettingsService $settings, StickerLayoutService $layout, StickerPricingService $pricing): JsonResponse
    {
        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1', 'max:80'],
            'lines.*.client_id' => ['nullable', 'string', 'max:80'],
            'lines.*.source' => ['required', 'in:library,upload'],
            'lines.*.library_item_id' => ['nullable', 'integer', 'exists:sticker_library_items,id'],
            'lines.*.upload_key' => ['nullable', 'string', 'max:80'],
            'lines.*.label' => ['nullable', 'string', 'max:120'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'lines.*.size_key' => ['required', 'string', 'max:40'],
            'lines.*.cut_type' => ['required', 'in:circle,contour'],
            'lines.*.image_width_px' => ['nullable', 'integer', 'min:1'],
            'lines.*.image_height_px' => ['nullable', 'integer', 'min:1'],
            'lines.*.image_url' => ['nullable', 'string', 'max:1000'],
        ]);

        $settingsData = $settings->get();
        $settingsData['materials'] = $this->customerMaterials();
        $validated['page_specs'] = $this->pageSpecs($request);
        $lines = $layout->normalizeLines($this->hydrateLibraryImages($validated['lines']), $settingsData);
        $quote = $pricing->quote($lines, array_merge($settingsData, ['page_specs' => $validated['page_specs']]));
        $snapshot = $layout->arrange($lines, $settingsData);
        $warnings = array_values(array_unique(array_merge($quote['warnings'], $layout->qualityWarnings($lines, $settingsData))));

        return response()->json([
            'ok' => true,
            'quote' => $quote,
            'layout_snapshot' => array_merge($snapshot, [
                'pricing' => $quote,
                'warnings' => $warnings,
            ]),
            'warnings' => $warnings,
            'settings' => $settingsData,
        ]);
    }

    public function store(Request $request, StickerSettingsService $settings, StickerLayoutService $layout, StickerPricingService $pricing): JsonResponse
    {
        $validated = $this->validatedStore($request, $settings->get());

        return DB::transaction(function () use ($request, $validated, $settings, $layout, $pricing) {
            $settingsData = $settings->get(); $settingsData['materials'] = $this->customerMaterials();
            $validated['page_specs'] = $this->pageSpecs($request);
            $product = Product::query()->where('code', 'STICKER-CUSTOM')->where('is_active', true)->firstOrFail();
            $variant = Variant::query()->where('product_id', $product->id)->where('is_active', true)->orderBy('id')->firstOrFail();
            $offering = $this->offering($product, $variant);
            $lines = $layout->normalizeLines($this->hydrateLibraryImages($validated['lines']), $settingsData);
            $quote = $pricing->quote($lines, array_merge($settingsData, ['page_specs' => $validated['page_specs']]));

            if ($quote['quantity'] < (int) $settingsData['pricing']['min_quantity']) {
                throw ValidationException::withMessages(['lines' => 'ط§ظ„ط­ط¯ ط§ظ„ط£ط¯ظ†ظ‰ ظ„ط·ظ„ط¨ ط§ظ„ظ…ظ„طµظ‚ط§طھ ظ‡ظˆ '.(int) $settingsData['pricing']['min_quantity'].' ظ…ظ„طµظ‚.']);
            }

            $snapshot = $layout->arrange($lines, $settingsData);
            $warnings = array_values(array_unique(array_merge($quote['warnings'], $layout->qualityWarnings($lines, $settingsData))));
            $cart = Cart::firstOrCreate(['user_id' => $request->user()->id, 'status' => 'active']);
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'design_id' => null,
                'item_type' => CartItem::TYPE_STICKER_BUNDLE,
                'quantity' => 1,
                'unit_price' => $quote['total'],
                'selected_options' => [
                    'design_name' => 'حزمة ملصقات A4',
                    'print_areas' => ['front'],
                    'printing_method' => 'vinyl-cut',
                    'printing_method_name' => 'Vinyl cut',
                    'branch_product_offering_id' => $offering->id,
                    'print_provider_branch_id' => $offering->print_provider_branch_id,
                    'sticker_quantity' => $quote['quantity'], 'page_specs' => $validated['page_specs'],
                    'sticker_page_count' => $snapshot['page_count'],
                    'sticker_quote' => $quote,
                    'sticker_settings_snapshot' => $settingsData,
                    'layout_snapshot' => array_merge($snapshot, ['pricing' => $quote, 'warnings' => $warnings]),
                    'warnings' => $warnings,
                ],
            ]);

            $this->storeLinesAndFiles($request, $product, $item, $lines);

            return response()->json([
                'ok' => true,
                'redirect' => route('customer.basket'),
                'cart_item_id' => $item->id,
            ]);
        });
    }

    public function update(Request $request, CartItem $cartItem, StickerSettingsService $settings, StickerLayoutService $layout, StickerPricingService $pricing): JsonResponse
    {
        abort_unless($request->user() && $cartItem->cart->user_id === $request->user()->id, 403);
        abort_unless($cartItem->item_type === CartItem::TYPE_STICKER_BUNDLE, 404);

        $validated = $this->validatedStore($request, $settings->get());

        return DB::transaction(function () use ($request, $validated, $settings, $layout, $pricing, $cartItem) {
            $settingsData = $settings->get(); $settingsData['materials'] = $this->customerMaterials();
            $validated['page_specs'] = $this->pageSpecs($request);
            $lines = $layout->normalizeLines($this->hydrateLibraryImages($validated['lines']), $settingsData);
            $quote = $pricing->quote($lines, array_merge($settingsData, ['page_specs' => $validated['page_specs']]));

            if ($quote['quantity'] < (int) $settingsData['pricing']['min_quantity']) {
                throw ValidationException::withMessages(['lines' => 'ط§ظ„ط­ط¯ ط§ظ„ط£ط¯ظ†ظ‰ ظ„ط·ظ„ط¨ ط§ظ„ظ…ظ„طµظ‚ط§طھ ظ‡ظˆ '.(int) $settingsData['pricing']['min_quantity'].' ظ…ظ„طµظ‚.']);
            }

            $snapshot = $layout->arrange($lines, $settingsData);
            $warnings = array_values(array_unique(array_merge($quote['warnings'], $layout->qualityWarnings($lines, $settingsData))));
            $options = $cartItem->selected_options ?? [];
            $cartItem->update([
                'unit_price' => $quote['total'],
                'selected_options' => array_merge($options, [
                    'sticker_quantity' => $quote['quantity'],
                    'page_specs' => $validated['page_specs'],
                    'sticker_page_count' => $snapshot['page_count'],
                    'sticker_quote' => $quote,
                    'sticker_settings_snapshot' => $settingsData,
                    'layout_snapshot' => array_merge($snapshot, ['pricing' => $quote, 'warnings' => $warnings]),
                    'warnings' => $warnings,
                ]),
            ]);

            $this->deleteStickerFilesForItem($cartItem);
            $cartItem->stickerBundleLines()->delete();
            $this->storeLinesAndFiles($request, $cartItem->product, $cartItem, $lines);

            return response()->json(['ok' => true, 'redirect' => route('customer.basket')]);
        });
    }

    /** @return array<string, mixed> */
    private function validatedStore(Request $request, array $settings): array
    {
        $maxFileKb = (int) $settings['upload']['max_file_mb'] * 1024;

        return $request->validate([
            'lines' => ['required', 'array', 'min:1', 'max:80'],
            'lines.*.client_id' => ['nullable', 'string', 'max:80'],
            'lines.*.source' => ['required', 'in:library,upload'],
            'lines.*.library_item_id' => ['nullable', 'integer', 'exists:sticker_library_items,id'],
            'lines.*.upload_key' => ['nullable', 'string', 'max:80'],
            'lines.*.label' => ['nullable', 'string', 'max:120'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'lines.*.size_key' => ['required', 'string', 'max:40'],
            'lines.*.cut_type' => ['required', 'in:circle,contour'],
            'lines.*.image_width_px' => ['nullable', 'integer', 'min:1'],
            'lines.*.image_height_px' => ['nullable', 'integer', 'min:1'],
            'files' => ['nullable', 'array', 'max:'.(int) $settings['upload']['max_files']],
            'files.*' => ['file', 'mimes:png,jpg,jpeg', 'max:'.$maxFileKb],
        ]);
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function hydrateLibraryImages(array $lines): array
    {
        $ids = collect($lines)->pluck('library_item_id')->filter()->unique()->values();
        $items = StickerLibraryItem::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return collect($lines)->map(function (array $line) use ($items) {
            if (($line['source'] ?? null) === 'library') {
                $item = $items->get((int) ($line['library_item_id'] ?? 0));
                if (! $item) {
                    throw ValidationException::withMessages(['lines' => 'ط£ط­ط¯ ط§ظ„ظ…ظ„طµظ‚ط§طھ ط؛ظٹط± ظ…طھط§ط­ ط­ط§ظ„ظٹط§ظ‹.']);
                }
                $line['label'] = $line['label'] ?? $item->name;
                $line['image_url'] = $item->imageUrl();
            }

            return $line;
        })->all();
    }

    private function offering(Product $product, Variant $variant): BranchProductOffering
    {
        $offering = BranchProductOffering::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->whereHas('printProviderBranch', fn ($query) => $query->where('is_active', true))
            ->whereHas('branchOfferingVariants', fn ($query) => $query->where('variant_id', $variant->id)->where('is_available', true))
            ->orderBy('base_price')
            ->first();

        if (! $offering) {
            throw ValidationException::withMessages(['lines' => 'ظ„ط§ طھظˆط¬ط¯ ظ…ط·ط¨ط¹ط© ظ…طھط§ط­ط© ظ„طھظ†ظپظٹط° ظ…ظ†طھط¬ ط§ظ„ظ…ظ„طµظ‚ط§طھ ط­ط§ظ„ظٹط§ظ‹.']);
        }

        return $offering;
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function storeLinesAndFiles(Request $request, Product $product, CartItem $item, array $lines): void
    {
        $files = $request->file('files', []);
        $savedUploads = [];

        foreach ($lines as $line) {
            if ($line['source'] !== StickerBundleLine::SOURCE_UPLOAD) {
                continue;
            }

            $key = $line['upload_key'] ?: $line['client_id'];
            $file = $files[$key] ?? null;
            if (! $file) {
                throw ValidationException::withMessages(['files' => 'ط§ط±ظپط¹ طµظˆط±ط© ظ„ظƒظ„ ظ…ظ„طµظ‚ ظ…ط®طµطµ.']);
            }

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $path = $file->storeAs('customer-stickers/tmp/'.$request->user()->id, Str::uuid().'.'.$extension, 'local');
            $savedUploads[$key] = PrintFile::create([
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
                'cart_item_id' => $item->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'preview_path' => $path,
                'preview_disk' => 'local',
                'disk' => 'local',
                'mime_type' => $file->getClientMimeType(),
                'extension' => $extension,
                'file_size' => $file->getSize(),
                'page_count' => 1,
                'status' => PrintFile::STATUS_ATTACHED_TO_CART,
                'uploaded_at' => now(),
            ]);
        }

        foreach ($lines as $line) {
            $uploadKey = $line['upload_key'] ?: $line['client_id'];
            StickerBundleLine::create([
                'cart_item_id' => $item->id,
                'source' => $line['source'],
                'client_id' => $line['client_id'],
                'upload_key' => $line['upload_key'],
                'sticker_library_item_id' => $line['source'] === StickerBundleLine::SOURCE_LIBRARY ? $line['library_item_id'] : null,
                'print_file_id' => $line['source'] === StickerBundleLine::SOURCE_UPLOAD ? ($savedUploads[$uploadKey]->id ?? null) : null,
                'quantity' => $line['quantity'],
                'size_key' => $line['size_key'],
                'width_mm' => $line['width_mm'],
                'height_mm' => $line['height_mm'],
                'cut_type' => $line['cut_type'],
                'label' => $line['label'],
                'notes' => $line['notes'],
                'sort_order' => $line['sort_order'],
            ]);
        }
    }

    private function deleteStickerFilesForItem(CartItem $cartItem): void
    {
        foreach ($cartItem->printFiles()->where('status', '!=', PrintFile::STATUS_DELETED)->get() as $file) {
            if ($file->stored_path) {
                Storage::disk($file->disk ?: 'local')->delete($file->stored_path);
            }
            $file->update(['status' => PrintFile::STATUS_DELETED, 'deleted_at' => now()]);
        }
    }

    /** @return array<string, mixed> */
    private function customerMaterials(): array
    {
        $offerings = BranchProductOffering::query()
            ->with('product')
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('code', 'STICKER-CUSTOM')->where('is_active', true))
            ->whereHas('printProviderBranch', fn ($q) => $q->where('is_active', true))
            ->get();

        $papers = [];
        $protection = null;
        foreach ($offerings as $offering) {
            $materials = $offering->sticker_materials ?: [];
            foreach (($materials['paper'] ?? []) as $paper) {
                if (! is_array($paper) || empty($paper['key']) || ! ($paper['available'] ?? false)) continue;
                $key = (string) $paper['key'];
                $papers[$key] ??= ['key' => $key, 'label' => (string) ($paper['label'] ?? $key), 'price_per_sheet' => (float) ($paper['price_per_sheet'] ?? 0), 'available' => true];
            }
            if (is_array($materials['protection'] ?? null) && ($materials['protection']['available'] ?? false)) {
                $protection ??= ['label' => (string) ($materials['protection']['label'] ?? 'طبقة حماية'), 'price_per_sheet' => (float) ($materials['protection']['price_per_sheet'] ?? 0), 'available' => true];
            }
        }

        return [
            'paper' => array_values($papers) ?: [
                ['key' => 'photo-sticker', 'label' => 'فوتو لاصق', 'price_per_sheet' => 0, 'available' => true],
                ['key' => 'photo-non-adhesive', 'label' => 'فوتو غير لاصق', 'price_per_sheet' => 0, 'available' => true],
            ],
            'protection' => $protection ?: ['label' => 'طبقة حماية', 'price_per_sheet' => 0, 'available' => false],
        ];
    }

    /** @return array<int, array{paper_key:string, protection:bool}> */
    private function pageSpecs(Request $request): array
    {
        $raw = $request->input('page_specs', []);
        if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
        if (! is_array($raw)) return [];
        return array_values(array_map(fn ($spec) => [
            'paper_key' => substr((string) ($spec['paper_key'] ?? 'photo-sticker'), 0, 60),
            'protection' => filter_var($spec['protection'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ], array_filter($raw, 'is_array')));
    }
}
