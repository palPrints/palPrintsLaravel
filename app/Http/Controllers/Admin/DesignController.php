<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Design;
use App\Models\Notification;
use App\Support\CatalogProductData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DesignController extends Controller
{
    /** Database status => label/state used by the page. */
    private const STATES = [
        'review' => 'pending',
        'submitted' => 'pending',
        'published' => 'approved',
        'rejected' => 'rejected',
    ];

    private const BADGES = ['arts', 'national', 'calligraphy', 'heritage'];

    public function index(): View
    {
        $designs = Design::query()
            ->with(['designer:id,name', 'product:id,name,code,category_id', 'product.category:id,name,slug', 'product.branchProductOfferings.branchPrintAreas'])
            ->whereIn('status', array_keys(self::STATES))
            ->latest('submitted_at')
            ->latest('id')
            ->get();

        // Colours are stored by code; the page shows their names.
        $colorCodes = $designs->flatMap(fn (Design $design) => array_merge(
            (array) ($design->selected_options['allowed_color_ids'] ?? []),
            array_filter([$design->selected_options['color_id'] ?? null])
        ))->unique()->values();
        // A colour is stored as {"name": ..., "hex": ...} (or a plain name for the older ones): decode it for display.
        $colorInfo = AttributeValue::whereIn('code', $colorCodes)->get()
            ->groupBy('code')
            ->map(fn ($values) => CatalogProductData::describeColor($values->first()));
        $colorNames = $colorInfo->map(fn (array $color) => $color['name']);
        $colorEntry = fn (string $code): array => ['id' => $code, 'name' => $colorInfo[$code]['name'] ?? $code, 'hex' => $colorInfo[$code]['hex'] ?? null];

        $designs = $designs->map(function (Design $design) use ($colorNames, $colorEntry) {
                $category = $design->product?->category;
                $date = $design->submitted_at ?? $design->created_at;
                $options = $design->selected_options ?? [];
                $audience = CatalogProductData::AUDIENCES[$options['display_category'] ?? ''] ?? null;
                $sizeNames = $audience
                    ? collect($audience['sizes'])->whereIn('id', $options['allowed_size_ids'] ?? [])->pluck('name')->all()
                    : [];

                return [
                    'id' => $design->id,
                    'code' => 'DSN-'.str_pad((string) $design->id, 3, '0', STR_PAD_LEFT),
                    'title' => $design->title,
                    'image' => $this->imageUrl($design->image),
                    'category' => $category?->slug ?? 'other',
                    'categoryLabel' => $category ? CatalogProductData::categoryName($category->slug, $category->name) : 'غير مصنف',
                    // The page styles four badge colours; categories cycle through them.
                    'categoryClass' => self::BADGES[(($category?->id ?? 1) - 1) % count(self::BADGES)],
                    'designer' => $design->designer?->name ?? 'مصمم محذوف',
                    'product' => $design->product?->name,
                    'date' => $date?->locale('ar')->translatedFormat('j F Y'),
                    'state' => self::STATES[$design->status],
                    'rejectionReason' => $design->rejection_reason,
                    'basePrice' => number_format((float) $design->base_price, 2).' ₪',
                    'sellingPrice' => number_format((float) $design->selling_price, 2).' ₪',
                    'profit' => number_format((float) $design->designer_profit, 2).' ₪',
                    'audience' => $audience['label'] ?? '',
                    'sizes' => implode('، ', $sizeNames),
                    'colors' => collect($options['allowed_color_ids'] ?? [])->map(fn ($code) => $colorNames[$code] ?? $code)->implode('، '),
                    'previewColor' => $colorNames[$options['color_id'] ?? ''] ?? '',
                    'details' => $this->details($design, $options, $colorEntry),
                ];
            });

        return view('admin.designs', [
            'designs' => $designs,
            'counts' => [
                'total' => $designs->count(),
                'pending' => $designs->where('state', 'pending')->count(),
                'approved' => $designs->where('state', 'approved')->count(),
                'rejected' => $designs->where('state', 'rejected')->count(),
            ],
            'categories' => Category::where('is_active', true)->orderBy('id')->get(['slug', 'name'])
                ->mapWithKeys(fn (Category $category) => [$category->slug => CatalogProductData::categoryName($category->slug, $category->name)]),
        ]);
    }

    public function review(Request $request, Design $design): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($design->status, ['review', 'submitted'], true)) {
            return $this->respond($request, false, 'هذا التصميم ليس بانتظار المراجعة.', 422);
        }

        $approved = $validated['action'] === 'approve';

        DB::transaction(function () use ($request, $design, $approved, $validated) {
            $design->update([
                'status' => $approved ? 'published' : 'rejected',
                'reviewed_at' => now(),
                'published_at' => $approved ? now() : null,
                'rejection_reason' => $approved ? null : ($validated['reason'] ?? null),
            ]);

            Notification::create([
                'user_id' => $design->designer_id,
                'type' => 'design_review',
                'title' => $approved ? 'تم اعتماد تصميمك' : 'تم رفض تصميمك',
                'message' => $approved
                    ? 'تم اعتماد تصميم «'.$design->title.'» وأصبح منشورًا.'
                    : 'تم رفض تصميم «'.$design->title.'». راجع ملاحظات الإدارة وعدّل التصميم.',
                'link' => route('designer.designs.index'),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => $approved ? 'admin.design_approved' : 'admin.design_rejected',
                'description' => 'مراجعة التصميم رقم '.$design->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'new_values' => ['design_id' => $design->id, 'status' => $design->status],
            ]);
        });

        return $this->respond($request, true, $approved ? 'تم اعتماد التصميم بنجاح.' : 'تم رفض التصميم.');
    }

    /**
     * What the details dialog draws: the colours, the print areas the shops offer for the product, and the design's
     * layout on every area (the artwork files are private, so the page gets an admin-only link for each).
     *
     * @return array<string, mixed>
     */
    private function details(Design $design, array $options, \Closure $colorEntry): array
    {
        $code = strtolower((string) $design->product?->code);
        $kind = match (true) {
            str_contains($code, 'tshirt') => 'tshirts',
            str_contains($code, 'hoodie') => 'hoodies',
            str_contains($code, 'mug') => 'mugs',
            str_contains($code, 'tote'), str_contains($code, 'bag') => 'bags',
            str_contains($code, 'cap') => 'caps',
            default => null,
        };

        $areas = ($design->product?->branchProductOfferings ?? collect())
            ->filter(fn ($offering) => $offering->is_active)
            ->flatMap(fn ($offering) => $offering->branchPrintAreas)
            ->where('is_active', true)
            ->unique('code')
            ->map(fn ($area) => ['id' => $area->code, 'name' => $area->name])
            ->values()
            ->all();

        $payload = (array) $design->design_payload;
        $files = collect($payload['files'] ?? [])->mapWithKeys(fn (array $file) => [
            $file['asset_id'] => route('admin.designs.file', [$design, $file['asset_id']]),
        ])->all();

        return [
            'kind' => $kind,
            'previewColor' => isset($options['color_id']) ? $colorEntry((string) $options['color_id']) : null,
            'colors' => collect($options['allowed_color_ids'] ?? [])->map(fn ($colorCode) => $colorEntry((string) $colorCode))->values()->all(),
            'areas' => $areas,
            'layout' => $payload['layout']['areas'] ?? [],
            'files' => $files,
        ];
    }

    /** One artwork file of a design, for the admin's review only (the files are kept private). */
    public function file(Design $design, string $assetId)
    {
        $file = collect($design->design_payload['files'] ?? [])->firstWhere('asset_id', $assetId);

        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->response($file['path'], null, [
            'Content-Type' => $file['mime_type'] ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    private function respond(Request $request, bool $ok, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message], $status);
        }

        return back()->with($ok ? 'success' : 'warning', $message);
    }

    private function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return preg_match('#^(https?:)?//#', $path) ? $path : asset($path);
    }
}
