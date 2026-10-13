<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Models\BranchProductOffering;
use App\Models\Design;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use App\Support\CatalogProductData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DesignController extends Controller
{
    public function index()
    {
        $designs = Design::query()
            ->with('product')
            ->withCount(['orderItems', 'cartItems'])
            ->where('designer_id', Auth::id())
            ->latest()
            ->get();

        return view('designer.designs.index', compact('designs'));
    }

    /**
     * What the designer may do with a design. A design that was bought (or sits in a cart) is part of someone's order,
     * so it is never changed or deleted: the designer pulls it from the store, or makes a copy and edits that.
     *
     * @return array{edit: bool, delete: bool, unpublish: bool, duplicate: bool}
     */
    public static function abilities(Design $design): array
    {
        $sold = (int) ($design->order_items_count ?? $design->orderItems()->count());
        $inCarts = (int) ($design->cart_items_count ?? $design->cartItems()->count());
        $notLive = in_array($design->status, ['draft', 'rejected'], true);

        return [
            'edit' => $notLive && $sold === 0,
            // A design waiting for review can also be deleted (the admin simply stops seeing it).
            'delete' => ($notLive || $design->status === 'review') && $sold === 0 && $inCarts === 0,
            'unpublish' => $design->status === 'published',
            // A design the admin is still to review is pulled back to a draft first, so the admin never approves a version
            // the designer is changing at that moment.
            'withdraw' => $design->status === 'review' && $sold === 0,
            // Offered where the design itself cannot be edited; an editable one is simply edited. (The server still copies any of their own.)
            'duplicate' => ! ($notLive && $sold === 0),
        ];
    }

    /** Why a design cannot be edited or deleted, in words for the designer; null when it can be edited. */
    public static function lockReason(Design $design): ?string
    {
        if (self::abilities($design)['edit']) {
            return null;
        }

        return match ($design->status) {
            'published' => 'منشور: لا يُعدَّل ولا يُحذف. يمكنك إيقاف نشره أو نسخه للتعديل.',
            'review' => 'قيد المراجعة: اسحبه من المراجعة لتعدّل عليه (وأرسله من جديد)، أو احذفه.',
            default => 'طلبه عملاء: لا يُعدَّل ولا يُحذف. انسخه وعدّل على النسخة.',
        };
    }

    /** Everything the design studio needs to open a saved design again (see design-studio.blade.php). */
    public static function studioData(Design $design): array
    {
        $options = $design->selected_options ?? [];
        $payload = (array) $design->design_payload;
        $layout = (array) ($payload['layout'] ?? []);
        $files = collect($payload['files'] ?? [])->filter(fn ($file) => ! empty($file['asset_id']));
        $records = collect($payload['assets'] ?? [])->keyBy('assetId');

        return [
            'id' => $design->id,
            'name' => $design->title,
            'sellingPrice' => (float) $design->selling_price,
            'productCode' => strtoupper((string) ($payload['product_code'] ?? $design->product?->code)),
            'colorId' => $layout['colorId'] ?? ($options['color_id'] ?? null),
            'sizeId' => $layout['sizeId'] ?? ($options['size_id'] ?? null),
            'category' => $options['display_category'] ?? null,
            'allowedColorIds' => (array) ($options['allowed_color_ids'] ?? []),
            'allowedSizeIds' => (array) ($options['allowed_size_ids'] ?? []),
            'areas' => (object) ($layout['areas'] ?? []),
            // The studio's record of each picture; rebuilt from the saved file when an older design has none.
            'assets' => $files->map(fn ($file) => $records->get($file['asset_id']) ?? [
                'assetId' => $file['asset_id'],
                'name' => $file['name'] ?? $file['asset_id'],
                'mimeType' => $file['mime_type'] ?? 'image/png',
                'size' => $file['size'] ?? 0,
                'lastModified' => 0,
                'fingerprint' => 'saved:'.$file['asset_id'],
                'pixelWidth' => null,
                'pixelHeight' => null,
                'sourceType' => ($file['mime_type'] ?? '') === 'image/svg+xml' ? 'vector' : 'raster',
                'createdAt' => now()->toIso8601String(),
            ])->values()->all(),
            'files' => $files->map(fn ($file) => [
                'assetId' => $file['asset_id'],
                'url' => route('designer.designs.file', [$design, $file['asset_id']]),
            ])->values()->all(),
        ];
    }

    /** One artwork file of the designer's own design, so the studio can load it again. */
    public function file(Design $design, string $assetId)
    {
        abort_unless($design->designer_id === Auth::id(), 404);

        $file = collect($design->design_payload['files'] ?? [])->firstWhere('asset_id', $assetId);
        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->response($file['path'], null, [
            'Content-Type' => $file['mime_type'] ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Request $request, Design $design): JsonResponse
    {
        abort_unless($design->designer_id === Auth::id(), 404);

        // Checked again under a lock: the admin approving a design at the same moment must not lose it to this delete.
        $deleted = DB::transaction(function () use ($design) {
            $locked = Design::query()->withCount(['orderItems', 'cartItems'])->lockForUpdate()->find($design->id);

            if (! $locked || ! self::abilities($locked)['delete']) {
                return $locked;
            }

            $locked->delete();
            $this->deleteFiles($locked); // the row is gone first; its files are removed after

            return null;
        });

        if ($deleted) {
            return response()->json(['message' => $deleted->status === 'published'
                ? 'لا يمكن حذف تصميم منشور. أوقف نشره أولًا.'
                : 'لا يمكن حذف هذا التصميم لأنه مرتبط بطلبات أو بسلال عملاء.'], 422);
        }

        return response()->json(['message' => 'تم حذف التصميم.']);
    }

    /** Pulls a design back from the admin's review queue to a draft, then the designer edits it and sends it again. */
    public function withdraw(Request $request, Design $design): JsonResponse
    {
        abort_unless($design->designer_id === Auth::id(), 404);

        $design->loadCount(['orderItems', 'cartItems']);

        // Locked, so the admin approving it at the same moment and this withdrawal cannot both win.
        $withdrawn = DB::transaction(function () use ($design) {
            $locked = Design::query()->lockForUpdate()->find($design->id);

            if ($locked->status !== 'review') {
                return false;
            }

            $locked->update(['status' => 'draft', 'submitted_at' => null]);

            return true;
        });

        if (! $withdrawn) {
            return response()->json(['message' => 'لم يعد هذا التصميم قيد المراجعة، حدّث الصفحة.'], 422);
        }

        return response()->json([
            'message' => 'تم سحب التصميم من المراجعة، وصار مسودة.',
            'redirect' => route('design-studio', ['edit' => $design->id]),
        ]);
    }

    /** Takes a published design off the store. It goes back to a draft; customers' carts lose it, past orders keep it. */
    public function unpublish(Request $request, Design $design): JsonResponse
    {
        abort_unless($design->designer_id === Auth::id(), 404);

        if ($design->status !== 'published') {
            return response()->json(['message' => 'هذا التصميم غير منشور.'], 422);
        }

        DB::transaction(function () use ($design) {
            // A design that is not for sale cannot stay in a basket (and the cart row would block nothing else).
            $design->cartItems()->delete();
            $design->update(['status' => 'draft', 'published_at' => null]);
        });

        return response()->json(['message' => 'تم إيقاف نشر التصميم، وصار مسودة.']);
    }

    /** A new draft made from any of the designer's designs, with its own copy of the picture and artwork. */
    public function duplicate(Request $request, Design $design): JsonResponse
    {
        abort_unless($design->designer_id === Auth::id(), 404);

        $copy = DB::transaction(function () use ($design) {
            $copy = $design->replicate(['status', 'rejection_reason', 'submitted_at', 'reviewed_at', 'published_at', 'image', 'design_payload']);
            $copy->title = Str::limit('نسخة من '.$design->title, 100, '');
            $copy->status = 'draft';
            $copy->save();

            $payload = (array) $design->design_payload;
            $files = [];
            foreach ($payload['files'] ?? [] as $file) {
                if (empty($file['path']) || ! Storage::disk('local')->exists($file['path'])) {
                    continue;
                }
                $extension = strtolower(pathinfo((string) $file['path'], PATHINFO_EXTENSION));
                $path = 'designer-designs/'.$copy->designer_id.'/'.$copy->id.'/'.Str::uuid().($extension ? '.'.$extension : '');
                Storage::disk('local')->copy($file['path'], $path);
                $files[] = ['path' => $path] + $file;
            }

            $image = null;
            $previewPath = ltrim(Str::after((string) $design->image, 'storage/'), '/');
            if ($design->image && str_starts_with($design->image, 'storage/') && Storage::disk('public')->exists($previewPath)) {
                $image = 'designs/'.$copy->id.'-'.Str::random(8).'.png';
                Storage::disk('public')->copy($previewPath, $image);
                $image = 'storage/'.$image;
            }

            $copy->update(['image' => $image, 'design_payload' => ['files' => $files] + $payload]);

            return $copy;
        });

        return response()->json(['message' => 'تم إنشاء نسخة كمسودة، افتحيها للتعديل.', 'id' => $copy->id], 201);
    }

    /** Removes a design's picture and its private artwork from the disks. */
    private function deleteFiles(Design $design): void
    {
        if (str_starts_with((string) $design->image, 'storage/')) {
            Storage::disk('public')->delete(Str::after($design->image, 'storage/'));
        }

        Storage::disk('local')->deleteDirectory('designer-designs/'.$design->designer_id.'/'.$design->id);
    }

    /** A saved design as the designer sent it: picture, product, prices, approved colours, and its review status. */
    public function show(Design $design)
    {
        abort_unless($design->designer_id === Auth::id(), 404);

        $options = $design->selected_options ?? [];
        $colors = AttributeValue::whereIn('code', (array) ($options['allowed_color_ids'] ?? []))->get()
            ->map(fn (AttributeValue $value) => CatalogProductData::describeColor($value))
            ->all();
        $audience = CatalogProductData::AUDIENCES[$options['display_category'] ?? ''] ?? null;

        return view('designer.designs.show', [
            'design' => $design->load('product'),
            'colors' => $colors,
            'audience' => $audience['label'] ?? null,
            'sizes' => $audience ? collect($audience['sizes'])->whereIn('id', $options['allowed_size_ids'] ?? [])->pluck('name')->all() : [],
        ]);
    }

    public function create()
    {
        return view('designer.designs.create', [
            'designerCatalog' => CatalogProductData::forDesigner(),
        ]);
    }

    public function review()
    {
        // The product's cost for the designer: the cheapest active print shop's base price, from the database.
        $basePrices = Product::query()
            ->where('is_active', true)
            ->whereNotIn('code', CatalogProductData::NOT_DESIGNABLE)
            ->get(['id', 'code'])
            ->mapWithKeys(fn (Product $product) => [
                strtoupper($product->code) => (float) (BranchProductOffering::query()
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->min('base_price') ?? 0),
            ]);

        return view('designer.designs.review', ['basePrices' => $basePrices]);
    }

    /**
     * Saves a design from the shared design studio. The browser sends one multipart request:
     *  - data:       JSON with the product code, name, selling price, chosen colors, layout and mockup
     *  - preview:    PNG of the design on the product (shown in the gallery once an admin approves it)
     *  - files[]:    the artwork images the designer uploaded (kept private, for printing)
     *  - file_assets[]: the studio's id of each file, so the layout can be matched to them later
     * The cost is read from the database here; the browser's value is never trusted.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'data' => ['required', 'json', 'max:400000'],
            'preview' => ['required', 'file', 'mimes:png', 'max:4096'],
            'files' => ['nullable', 'array', 'max:20'],
            'files.*' => ['file', 'mimes:png,jpg,jpeg,webp,svg', 'max:10240'],
            'file_assets' => ['nullable', 'array', 'max:20'],
            'file_assets.*' => ['string', 'max:120'],
        ]);

        $data = Validator::make(json_decode($request->input('data'), true) ?: [], [
            'status' => ['required', 'in:draft,submitted'],
            'productCode' => ['required', 'string', 'max:80'],
            'colorId' => ['nullable', 'string', 'max:80'],
            'sizeId' => ['nullable', 'string', 'max:80'],
            'designName' => ['required', 'string', 'max:100'],
            'sellingPrice' => ['required', 'numeric', 'min:0'],
            'rightsConfirmed' => ['exclude_if:status,draft', 'accepted'],
            'allowedColorIds' => ['nullable', 'array', 'max:40'],
            'allowedColorIds.*' => ['string', 'max:80'],
            'category' => ['nullable', 'string', 'in:'.implode(',', array_keys(CatalogProductData::AUDIENCES))],
            'allowedSizeIds' => ['nullable', 'array', 'max:20'],
            'allowedSizeIds.*' => ['string', 'max:20'],
            'mockup' => ['nullable', 'array'],
            'layout' => ['nullable', 'array'],
            'assets' => ['nullable', 'array', 'max:40'],
            // Set when the designer reopened a saved draft or rejected design: that design is updated instead of a new one made.
            'editingDesignId' => ['nullable', 'integer'],
        ], [], [
            'designName' => 'اسم التصميم',
            'sellingPrice' => 'سعر البيع',
            'rightsConfirmed' => 'تأكيد الحقوق',
        ])->validate();

        $product = Product::query()
            ->where('is_active', true)
            ->whereNotIn('code', CatalogProductData::NOT_DESIGNABLE)
            ->whereRaw('upper(code) = ?', [strtoupper($data['productCode'])])
            ->first();

        if (! $product) {
            throw ValidationException::withMessages(['productCode' => 'هذا المنتج غير متاح للتصميم حاليًا.']);
        }

        $basePrice = (float) (BranchProductOffering::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->min('base_price') ?? 0);
        $sellingPrice = (float) $data['sellingPrice'];

        if ($data['status'] === 'submitted' && $sellingPrice < $basePrice) {
            throw ValidationException::withMessages([
                'sellingPrice' => 'يجب ألا يقل سعر البيع عن تكلفة المنتج ('.number_format($basePrice, 2).' ₪).',
            ]);
        }

        // Clothing is for an audience (men/women, oversized, kids); the sizes offered must belong to it.
        $category = null;
        $allowedSizeIds = [];
        if (CatalogProductData::isApparel($product->code)) {
            $category = $data['category'] ?? null;
            if (! $category) {
                throw ValidationException::withMessages(['category' => 'اختر الفئة المناسبة للتصميم.']);
            }

            $audienceSizes = array_column(CatalogProductData::AUDIENCES[$category]['sizes'], 'id');
            $allowedSizeIds = array_values(array_intersect($data['allowedSizeIds'] ?? $audienceSizes, $audienceSizes));
            if ($data['status'] === 'submitted' && ! $allowedSizeIds) {
                throw ValidationException::withMessages(['allowedSizeIds' => 'حدد مقاسًا واحدًا على الأقل مناسبًا لهذا التصميم.']);
            }
        }

        $status = $data['status'] === 'submitted' ? 'review' : 'draft';
        $designer = $request->user();

        // Reopened design: only the designer's own draft or rejected design that nobody has bought can be changed.
        $existing = null;
        if (! empty($data['editingDesignId'])) {
            $existing = Design::query()->withCount(['orderItems', 'cartItems'])
                ->where('designer_id', $designer->id)->find($data['editingDesignId']);

            if (! $existing || ! self::abilities($existing)['edit']) {
                throw ValidationException::withMessages([
                    'designName' => 'لا يمكن تعديل هذا التصميم (منشور أو قيد المراجعة أو مرتبط بطلبات). اصنعي نسخة منه وعدّلي عليها.',
                ]);
            }
        }

        $oldImage = $existing?->image;
        $oldFiles = collect($existing?->design_payload['files'] ?? []);

        $design = DB::transaction(function () use ($request, $data, $product, $basePrice, $sellingPrice, $status, $designer, $category, $allowedSizeIds, $existing, $oldFiles) {
            $fields = [
                'designer_id' => $designer->id,
                'product_id' => $product->id,
                'title' => $data['designName'],
                'description' => $product->name,
                'base_price' => $basePrice,
                'selling_price' => $sellingPrice,
                'designer_profit' => max(0, $sellingPrice - $basePrice),
                'selected_options' => [
                    'color_id' => $data['colorId'] ?? null,
                    'size_id' => $data['sizeId'] ?? null,
                    'allowed_color_ids' => $data['allowedColorIds'] ?? [],
                    'display_category' => $category,
                    'allowed_size_ids' => $allowedSizeIds,
                ],
                'status' => $status,
                'submitted_at' => $status === 'review' ? now() : null,
            ];

            if ($existing) {
                // A new review starts from scratch: the old verdict no longer applies.
                $design = tap($existing)->update($fields + ['rejection_reason' => null, 'reviewed_at' => null, 'published_at' => null]);
            } else {
                $design = Design::create($fields + ['image' => null]);
            }

            // Public picture of the design on the product: the gallery shows it after approval.
            $previewPath = $request->file('preview')->storeAs('designs', $design->id.'-'.Str::random(8).'.png', 'public');

            // The artwork stays private; the layout refers to it by the studio's asset id.
            $files = [];
            foreach ($request->file('files', []) as $index => $file) {
                $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
                $path = $file->storeAs('designer-designs/'.$designer->id.'/'.$design->id, Str::uuid().($extension ? '.'.$extension : ''), 'local');
                $files[] = [
                    'asset_id' => $request->input('file_assets.'.$index),
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            }

            // A picture the layout still uses but the browser did not send again keeps its saved file.
            $sent = collect($files)->pluck('asset_id');
            $used = collect(data_get($data, 'layout.areas', []))->flatMap(fn ($area) => collect($area['objects'] ?? [])->pluck('assetId'))->filter()->unique();
            $oldFiles->each(function ($old) use ($used, $sent, &$files) {
                if (! empty($old['asset_id']) && $used->contains($old['asset_id']) && ! $sent->contains($old['asset_id'])) {
                    $files[] = $old;
                }
            });

            $design->update([
                'image' => 'storage/'.$previewPath,
                'design_payload' => [
                    'product_code' => $product->code,
                    'allowed_color_ids' => $data['allowedColorIds'] ?? [],
                    'layout' => $data['layout'] ?? null,
                    'mockup' => $data['mockup'] ?? null,
                    'assets' => $data['assets'] ?? [],
                    'files' => $files,
                ],
            ]);

            return $design;
        });

        // The replaced picture and artwork are removed only now that the new ones are safely saved.
        if ($existing) {
            if (str_starts_with((string) $oldImage, 'storage/')) {
                Storage::disk('public')->delete(Str::after($oldImage, 'storage/'));
            }
            $keptPaths = collect($design->design_payload['files'] ?? [])->pluck('path');
            $oldFiles->pluck('path')->filter()->reject(fn ($path) => $keptPaths->contains($path))
                ->each(fn ($path) => Storage::disk('local')->delete($path));
        }

        if ($status === 'review') {
            // The designer's own confirmation, in their notifications.
            Notification::create([
                'user_id' => $designer->id,
                'type' => 'design.submitted',
                'title' => 'تم إرسال تصميمك للمراجعة',
                'message' => 'وصل التصميم «'.$design->title.'» إلى فريق الإدارة، وسنُعلمك عند الموافقة عليه.',
                'link' => route('designer.designs.index'),
            ]);

            // Reaches the admins' designs page (status "review") and their notifications.
            User::role('admin')->get()->each(fn (User $admin) => Notification::create([
                'user_id' => $admin->id,
                'type' => 'design.submitted',
                'title' => 'تصميم جديد بانتظار المراجعة',
                'message' => 'أرسل '.$designer->name.' التصميم «'.$design->title.'» للمراجعة.',
                'link' => route('admin.designs'),
            ]));
        }

        return response()->json([
            'id' => $design->id,
            'status' => $design->status,
            'message' => $status === 'review' ? 'تم إرسال التصميم للمراجعة.' : 'تم حفظ المسودة.',
        ], 201);
    }
}
