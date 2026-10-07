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
            ->where('designer_id', Auth::id())
            ->latest()
            ->get();

        return view('designer.designs.index', compact('designs'));
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

        $design = DB::transaction(function () use ($request, $data, $product, $basePrice, $sellingPrice, $status, $designer, $category, $allowedSizeIds) {
            $design = Design::create([
                'designer_id' => $designer->id,
                'product_id' => $product->id,
                'title' => $data['designName'],
                'description' => $product->name,
                'image' => null,
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
            ]);

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

            $design->update([
                'image' => 'storage/'.$previewPath,
                'design_payload' => [
                    'product_code' => $product->code,
                    'allowed_color_ids' => $data['allowedColorIds'] ?? [],
                    'layout' => $data['layout'] ?? null,
                    'mockup' => $data['mockup'] ?? null,
                    'files' => $files,
                ],
            ]);

            return $design;
        });

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
