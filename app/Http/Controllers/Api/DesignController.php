<?php

namespace App\Http\Controllers\Api;

use App\Models\Design;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DesignController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in($this->statuses())],
            'designer_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        $designs = Design::query()
            ->with($this->relations())
            ->when(! $isAdmin, function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('status', 'published');

                    if ($user->role === 'designer') {
                        $query->orWhere('designer_id', $user->id);
                    }
                });
            })
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['designer_id'] ?? null, fn ($query, int $designerId) => $query->where('designer_id', $designerId))
            ->when(array_key_exists('is_featured', $validated), fn ($query) => $query->where('is_featured', $validated['is_featured']))
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->success($designs, 'تم جلب التصاميم بنجاح.');
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'designer' || ! $this->hasPermission($user, 'create designs')) {
            return $this->error('إضافة التصاميم متاحة للمصممين الموافق عليهم فقط.', 403);
        }

        $validated = $request->validate($this->designRules(false));

        $design = DB::transaction(function () use ($user, $validated): Design {
            $design = $user->designs()->create([
                ...Arr::except($validated, ['products']),
                'status' => $validated['status'] ?? 'draft',
                'royalty_percentage' => 0,
                'is_featured' => false,
            ]);

            if (array_key_exists('products', $validated)) {
                $this->syncProducts($design, $validated['products'] ?? []);
            }

            return $design;
        });

        return $this->success(
            $design->load($this->relations()),
            'تم إنشاء التصميم بنجاح.',
            201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $design = Design::with($this->relations())->findOrFail($id);
        $user = $request->user();

        if (
            $design->status !== 'published'
            && ! $this->isAdmin($user)
            && $design->designer_id !== $user->id
        ) {
            return $this->error('ليس لديك صلاحية لعرض هذا التصميم.', 403);
        }

        return $this->success($design, 'تم جلب التصميم بنجاح.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $design = Design::findOrFail($id);
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        if (! $isAdmin && $design->designer_id !== $user->id) {
            return $this->error('لا يمكنك تعديل تصميم لا تملكه.', 403);
        }

        if (! $isAdmin && ! $this->hasPermission($user, 'edit designs')) {
            return $this->error('ليس لديك صلاحية لتعديل التصاميم.', 403);
        }

        $validated = $request->validate($this->designRules(true, $isAdmin));

        DB::transaction(function () use ($design, $validated, $isAdmin): void {
            $data = Arr::except($validated, ['products']);

            if (! $isAdmin) {
                $data = Arr::only($data, [
                    'title',
                    'description',
                    'image_url',
                    'file_url',
                    'file_size',
                    'file_format',
                    'base_price',
                    'status',
                ]);

                if (in_array($design->status, ['published', 'rejected', 'hidden'], true)) {
                    $data['status'] = 'pending';
                    $data['rejection_reason'] = null;
                    $data['published_at'] = null;
                }
            } else {
                if (($data['status'] ?? null) === 'published') {
                    $data['published_at'] = $design->published_at ?? now();
                    $data['rejection_reason'] = null;
                }

                if (($data['status'] ?? null) === 'rejected') {
                    $data['published_at'] = null;
                }
            }

            $design->update($data);

            if (array_key_exists('products', $validated)) {
                $this->syncProducts($design, $validated['products'] ?? []);
            }
        });

        return $this->success(
            $design->fresh()->load($this->relations()),
            'تم تعديل التصميم بنجاح.'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $design = Design::findOrFail($id);
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        if (! $isAdmin && $design->designer_id !== $user->id) {
            return $this->error('لا يمكنك حذف تصميم لا تملكه.', 403);
        }

        if (! $isAdmin && ! $this->hasPermission($user, 'delete designs')) {
            return $this->error('ليس لديك صلاحية لحذف التصاميم.', 403);
        }

        if ($design->designProducts()->whereHas('orderItems')->exists()) {
            return $this->error(
                'لا يمكن حذف تصميم مرتبط بطلبات سابقة. يمكن للإدارة إخفاؤه بدلًا من حذفه.',
                409
            );
        }

        $design->delete();

        return $this->success(null, 'تم حذف التصميم بنجاح.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function designRules(bool $updating, bool $isAdmin = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        $rules = [
            'title' => [$required, 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'image_url' => [$required, 'string', 'max:2048'],
            'file_url' => [$required, 'string', 'max:2048'],
            'file_size' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'file_format' => [$required, Rule::in(['jpg', 'jpeg', 'png', 'svg', 'pdf', 'ai', 'psd'])],
            'base_price' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['sometimes', Rule::in($isAdmin ? $this->statuses() : ['draft', 'pending'])],
            'products' => ['sometimes', 'nullable', 'array', 'max:100'],
            'products.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'products.*.designer_margin' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'products.*.position_x' => ['sometimes', 'integer'],
            'products.*.position_y' => ['sometimes', 'integer'],
            'products.*.scale' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'products.*.rotation' => ['sometimes', 'integer', 'min:-360', 'max:360'],
            'products.*.is_active' => ['sometimes', 'boolean'],
        ];

        if ($isAdmin) {
            $rules['royalty_percentage'] = ['sometimes', 'numeric', 'min:0', 'max:100'];
            $rules['is_featured'] = ['sometimes', 'boolean'];
            $rules['rejection_reason'] = ['nullable', 'required_if:status,rejected', 'string', 'max:2000'];
        }

        return $rules;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     */
    private function syncProducts(Design $design, array $products): void
    {
        $productIds = collect($products)->pluck('product_id');

        if (Product::whereIn('id', $productIds)->where('is_active', false)->exists()) {
            throw ValidationException::withMessages([
                'products' => ['لا يمكن ربط التصميم بمنتج غير مفعّل.'],
            ]);
        }

        foreach ($products as $product) {
            $design->designProducts()->updateOrCreate(
                ['product_id' => $product['product_id']],
                Arr::only($product, [
                    'designer_margin',
                    'position_x',
                    'position_y',
                    'scale',
                    'rotation',
                    'is_active',
                ])
            );
        }

        $removedProducts = $design->designProducts()
            ->when($productIds->isNotEmpty(), fn ($query) => $query->whereNotIn('product_id', $productIds));

        (clone $removedProducts)->whereHas('orderItems')->update(['is_active' => false]);
        $removedProducts->whereDoesntHave('orderItems')->delete();
    }

    /**
     * @return list<string>
     */
    private function statuses(): array
    {
        return ['draft', 'pending', 'published', 'rejected', 'hidden'];
    }

    /**
     * @return list<string>
     */
    private function relations(): array
    {
        return [
            'designer:id,name',
            'designProducts.product',
        ];
    }
}
