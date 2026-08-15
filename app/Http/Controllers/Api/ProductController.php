<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $isAdmin = $this->isAdmin($request->user());

        $products = Product::query()
            ->when(! $isAdmin, fn ($query) => $query->where('is_active', true))
            ->when(
                $isAdmin && array_key_exists('is_active', $validated),
                fn ($query) => $query->where('is_active', $validated['is_active'])
            )
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->success($products, 'تم جلب المنتجات بنجاح.');
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->hasPermission($request->user(), 'create products')) {
            return $this->error('ليس لديك صلاحية لإضافة المنتجات.', 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['slug'] = $this->uniqueSlug(
            $validated['slug'] ?? $validated['name']
        );

        $product = Product::create($validated);

        return $this->success($product, 'تم إنشاء المنتج بنجاح.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $product = Product::query()
            ->when(! $this->isAdmin($request->user()), fn ($query) => $query->where('is_active', true))
            ->withCount(['designProducts', 'printProviderProducts'])
            ->findOrFail($id);

        return $this->success($product, 'تم جلب المنتج بنجاح.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! $this->hasPermission($request->user(), 'edit products')) {
            return $this->error('ليس لديك صلاحية لتعديل المنتجات.', 403);
        }

        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'base_price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('slug', $validated) || array_key_exists('name', $validated)) {
            $validated['slug'] = $this->uniqueSlug(
                $validated['slug'] ?? $validated['name'],
                $product->id
            );
        }

        $product->update($validated);

        return $this->success($product->fresh(), 'تم تعديل المنتج بنجاح.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $this->hasPermission($request->user(), 'delete products')) {
            return $this->error('ليس لديك صلاحية لحذف المنتجات.', 403);
        }

        $product = Product::findOrFail($id);

        if ($product->designProducts()->exists() || $product->printProviderProducts()->exists()) {
            return $this->error(
                'لا يمكن حذف منتج مرتبط بتصاميم أو مطابع. يمكنك تعطيله بدلًا من حذفه.',
                409
            );
        }

        $product->delete();

        return $this->success(null, 'تم حذف المنتج بنجاح.');
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'product';
        $slug = $baseSlug;
        $counter = 2;

        while (Product::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
