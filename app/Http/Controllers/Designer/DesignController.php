<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Product;
use App\Support\CatalogProductData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function create()
    {
        return view('designer.designs.create', [
            'designerCatalog' => CatalogProductData::forDesigner(),
        ]);
    }

    public function editor()
    {
        return view('designer.designs.editor', [
            'designerCatalog' => CatalogProductData::keyedForDesigner(),
        ]);
    }

    public function review()
    {
        return view('designer.designs.review', [
            'designerCatalog' => CatalogProductData::keyedForDesigner(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,submitted'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'colorId' => ['nullable', 'string', 'max:80'],
            'sizeId' => ['nullable', 'string', 'max:80'],
            'designName' => ['required', 'string', 'max:100'],
            'pricing.basePrice' => ['nullable', 'numeric', 'min:0'],
            'pricing.sellingPrice' => ['required', 'numeric', 'min:0'],
            'pricing.profit' => ['nullable', 'numeric', 'min:0'],
            'printAreas' => ['nullable', 'array'],
            'warnings' => ['nullable', 'array'],
            'rightsConfirmed' => ['exclude_if:status,draft', 'accepted'],
        ]);

        $product = Product::query()
            ->where('is_active', true)
            ->findOrFail((int) $validated['productId']);

        $status = $validated['status'] === 'submitted' ? 'review' : 'draft';
        $pricing = $validated['pricing'] ?? [];
        $basePrice = (float) ($pricing['basePrice'] ?? 0);
        $sellingPrice = (float) $pricing['sellingPrice'];

        $design = Design::create([
            'designer_id' => $request->user()->id,
            'product_id' => $product->id,
            'title' => $validated['designName'],
            'description' => $product->name,
            'image' => null,
            'base_price' => $basePrice,
            'selling_price' => $sellingPrice,
            'designer_profit' => (float) ($pricing['profit'] ?? max(0, $sellingPrice - $basePrice)),
            'selected_options' => [
                'color_id' => $validated['colorId'] ?? null,
                'size_id' => $validated['sizeId'] ?? null,
            ],
            'design_payload' => $request->all(),
            'status' => $status,
            'submitted_at' => $status === 'review' ? now() : null,
        ]);

        return response()->json([
            'id' => $design->id,
            'status' => $design->status,
            'message' => $status === 'review' ? 'Design submitted for review.' : 'Draft saved.',
        ], 201);
    }
}
