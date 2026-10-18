<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StickerCategory;
use App\Models\StickerLibraryItem;
use App\Services\Stickers\StickerSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StickerController extends Controller
{
    public function index(StickerSettingsService $settings): View
    {
        return view('admin.stickers', [
            'stickerCategories' => $this->categories(),
            'stickerSettings' => $settings->get(),
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:sticker_categories,name'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        StickerCategory::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? ((int) StickerCategory::max('sort_order') + 10),
        ]);

        return response()->json(['ok' => true, 'categories' => $this->categories()]);
    }

    public function updateCategory(Request $request, StickerCategory $stickerCategory): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('sticker_categories', 'name')->ignore($stickerCategory->id)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $stickerCategory->update([
            'name' => $data['name'],
            'slug' => $stickerCategory->slug ?: $this->uniqueSlug($data['name']),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? $stickerCategory->sort_order,
        ]);

        return response()->json(['ok' => true, 'categories' => $this->categories()]);
    }

    public function destroyCategory(StickerCategory $stickerCategory): JsonResponse
    {
        foreach ($stickerCategory->stickers as $item) {
            Storage::disk($item->disk ?: 'public')->delete($item->image_path);
        }

        $stickerCategory->delete();

        return response()->json(['ok' => true, 'categories' => $this->categories()]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sticker_category_id' => ['required', 'integer', 'exists:sticker_categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->storeAs('stickers/library', Str::uuid().'.'.$extension, 'public');

        StickerLibraryItem::create([
            'sticker_category_id' => $data['sticker_category_id'],
            'name' => $data['name'],
            'image_path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? ((int) StickerLibraryItem::where('sticker_category_id', $data['sticker_category_id'])->max('sort_order') + 10),
        ]);

        return response()->json(['ok' => true, 'categories' => $this->categories()]);
    }

    public function updateItem(Request $request, StickerLibraryItem $stickerLibraryItem): JsonResponse
    {
        $data = $request->validate([
            'sticker_category_id' => ['required', 'integer', 'exists:sticker_categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $values = [
            'sticker_category_id' => $data['sticker_category_id'],
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? $stickerLibraryItem->sort_order,
        ];

        if ($request->hasFile('image')) {
            Storage::disk($stickerLibraryItem->disk ?: 'public')->delete($stickerLibraryItem->image_path);
            $file = $request->file('image');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $values += [
                'image_path' => $file->storeAs('stickers/library', Str::uuid().'.'.$extension, 'public'),
                'disk' => 'public',
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ];
        }

        $stickerLibraryItem->update($values);

        return response()->json(['ok' => true, 'categories' => $this->categories()]);
    }

    public function destroyItem(StickerLibraryItem $stickerLibraryItem): JsonResponse
    {
        Storage::disk($stickerLibraryItem->disk ?: 'public')->delete($stickerLibraryItem->image_path);
        $stickerLibraryItem->delete();

        return response()->json(['ok' => true, 'categories' => $this->categories()]);
    }

    public function updateSettings(Request $request, StickerSettingsService $settings): JsonResponse
    {
        $data = $request->validate([
            'pricing.min_quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'pricing.warning_quantity' => ['required', 'integer', 'min:1', 'max:5000'],
            'pricing.base_quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'pricing.base_price' => ['required', 'numeric', 'min:0', 'max:9999'],
            'pricing.extra_unit_price' => ['required', 'numeric', 'min:0', 'max:9999'],
            'pricing.contour_group_size' => ['required', 'integer', 'min:1', 'max:99'],
            'pricing.contour_group_price' => ['required', 'numeric', 'min:0', 'max:9999'],
            'upload.max_files' => ['required', 'integer', 'min:1', 'max:80'],
            'upload.max_file_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'upload.max_total_mb' => ['required', 'integer', 'min:1', 'max:200'],
            'layout.margin_top_mm' => ['required', 'numeric', 'min:0', 'max:50'],
            'layout.margin_right_mm' => ['required', 'numeric', 'min:0', 'max:50'],
            'layout.margin_bottom_mm' => ['required', 'numeric', 'min:0', 'max:50'],
            'layout.margin_left_mm' => ['required', 'numeric', 'min:0', 'max:50'],
            'layout.gap_mm' => ['required', 'numeric', 'min:0', 'max:30'],
            'layout.cut_line_width_mm' => ['required', 'numeric', 'min:0', 'max:5'],
            'layout.cut_line_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'layout.bleed_mm' => ['required', 'numeric', 'min:0', 'max:10'],
            'layout.safe_margin_mm' => ['required', 'numeric', 'min:0', 'max:10'],
            'layout.dpi' => ['required', 'integer', 'min:72', 'max:600'],
            'layout.sizes' => ['required', 'array', 'min:1', 'max:12'],
            'layout.sizes.*.key' => ['required', 'string', 'max:40'],
            'layout.sizes.*.label' => ['required', 'string', 'max:80'],
            'layout.sizes.*.width_mm' => ['required', 'numeric', 'min:5', 'max:190'],
            'layout.sizes.*.height_mm' => ['required', 'numeric', 'min:5', 'max:277'],
        ]);

        $updated = $settings->put(array_replace_recursive($settings->get(), $data));

        return response()->json(['ok' => true, 'settings' => $updated]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (StickerCategory::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function categories()
    {
        return StickerCategory::query()
            ->with(['stickers' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (StickerCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
                'stickers' => $category->stickers->map(fn (StickerLibraryItem $item) => [
                    'id' => $item->id,
                    'sticker_category_id' => $item->sticker_category_id,
                    'name' => $item->name,
                    'image_url' => $item->imageUrl(),
                    'is_active' => $item->is_active,
                    'sort_order' => $item->sort_order,
                ])->values(),
            ])->values();
    }
}
