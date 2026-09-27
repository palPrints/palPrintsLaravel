<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Design;
use App\Models\Notification;
use App\Support\CatalogProductData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->with(['designer:id,name', 'product:id,name,category_id', 'product.category:id,name,slug'])
            ->whereIn('status', array_keys(self::STATES))
            ->latest('submitted_at')
            ->latest('id')
            ->get()
            ->map(function (Design $design) {
                $category = $design->product?->category;
                $date = $design->submitted_at ?? $design->created_at;

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
