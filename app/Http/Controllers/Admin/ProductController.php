<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogProductData;
use App\Support\ProductVariants;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with('category:id,name,slug')
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'category' => $product->category ? CatalogProductData::categoryName($product->category->slug, $product->category->name) : null,
                'categoryId' => $product->category_id,
                'description' => $product->description,
                'image' => $this->imageUrl($product->image),
                'active' => $product->is_active,
            ] + ProductVariants::options($product));

        return view('admin.products', [
            'products' => $products,
            'categories' => Category::where('is_active', true)->orderBy('id')->get(['id', 'name', 'slug'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => CatalogProductData::categoryName($category->slug, $category->name),
                ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $options = $this->validatedOptions($request, true);
        $data = $this->validated($request); // stores the image, so only after everything else is valid

        $product = DB::transaction(function () use ($data, $options) {
            $product = Product::create($data);
            ProductVariants::sync($product, $options['colors'], $options['sizes']);

            return $product;
        });

        $this->log($request, 'admin.product_created', $product);

        return response()->json(['ok' => true, 'message' => 'تمت إضافة المنتج بنجاح.'], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $options = $this->validatedOptions($request, false);
        $data = $this->validated($request, $product); // stores the image, so only after everything else is valid
        $oldImage = $product->image;

        DB::transaction(function () use ($product, $data, $options) {
            $product->update($data);

            if ($options) {
                ProductVariants::sync($product, $options['colors'], $options['sizes']);
            }
        });

        if (isset($data['image']) && $this->isUploaded($oldImage)) {
            Storage::disk('public')->delete($this->storagePath($oldImage));
        }

        $this->log($request, 'admin.product_updated', $product);

        return response()->json(['ok' => true, 'message' => 'تم تحديث بيانات المنتج بنجاح.']);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        $this->log($request, 'admin.product_toggled', $product);

        return response()->json([
            'ok' => true,
            'active' => $product->is_active,
            'message' => $product->is_active ? 'تم تفعيل '.$product->name.'.' : 'تم إيقاف '.$product->name.' مؤقتًا.',
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        try {
            $product->delete();
        } catch (QueryException) {
            return response()->json([
                'ok' => false,
                'message' => 'لا يمكن حذف منتج مرتبط بتصاميم أو عروض. أوقفه مؤقتًا بدلًا من حذفه.',
            ], 422);
        }

        if ($this->isUploaded($product->image)) {
            Storage::disk('public')->delete($this->storagePath($product->image));
        }

        $this->log($request, 'admin.product_deleted', $product);

        return response()->json(['ok' => true, 'message' => 'تم حذف '.$product->name.'.']);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $request->merge(['code' => trim((string) $request->input('code'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('products', 'code')->ignore($product?->id)],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => [$product ? 'nullable' : 'required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ], [
            'code.unique' => 'رقم المنتج مستخدم مسبقًا.',
            'category_id.required' => 'اختر فئة المنتج.',
            'image.required' => 'صورة المنتج مطلوبة.',
            'image.image' => 'اختر ملف صورة صحيحًا.',
            'image.mimes' => 'اختر صورة بصيغة PNG أو JPG أو WEBP.',
            'image.max' => 'حجم الصورة أكبر من 5MB.',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = 'storage/'.$request->file('image')->store('products', 'public');
        } else {
            unset($validated['image']);
        }

        return $validated;
    }

    /**
     * The colours and sizes sent with the form. A new product must have at least one of each, because a product with
     * no variants cannot be offered by any shop; when editing they are optional and left alone if not sent.
     *
     * @return array{colors: array<int, array{code: ?string, name: string, hex: string}>, sizes: array<int, string>}|null
     */
    private function validatedOptions(Request $request, bool $required): ?array
    {
        if (! $required && ! $request->has('colors') && ! $request->has('sizes')) {
            return null;
        }

        $request->validate([
            'colors' => ['required', 'json'],
            'sizes' => ['required', 'string', 'max:300'],
        ], [
            'colors.required' => 'أضف لونًا واحدًا على الأقل.',
            'colors.json' => 'قائمة الألوان غير صحيحة.',
            'sizes.required' => 'أضف مقاسًا واحدًا على الأقل.',
        ]);

        $colors = json_decode((string) $request->input('colors'), true);
        $colors = is_array($colors) ? array_values($colors) : [];

        $validator = validator(['colors' => $colors], [
            'colors' => ['required', 'array', 'min:1', 'max:20'],
            'colors.*.name' => ['required', 'string', 'max:40'],
            'colors.*.hex' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.*.code' => ['nullable', 'string', 'max:60'],
        ], [
            'colors.required' => 'أضف لونًا واحدًا على الأقل.',
            'colors.min' => 'أضف لونًا واحدًا على الأقل.',
            'colors.max' => 'الحد الأقصى 20 لونًا.',
            'colors.*.name.required' => 'اكتب اسم كل لون.',
            'colors.*.hex.regex' => 'اختر رمز لون صحيحًا.',
        ]);
        $validator->validate();

        $hexes = collect($colors)->pluck('hex')->map(fn ($hex) => strtolower($hex));
        if ($hexes->count() !== $hexes->unique()->count()) {
            throw ValidationException::withMessages(['colors' => 'لا يمكن تكرار نفس اللون.']);
        }

        $sizes = collect(preg_split('/[,،\n]+/u', (string) $request->input('sizes')))
            ->map(fn ($size) => trim((string) $size))
            ->filter()
            ->unique(fn ($size) => ProductVariants::sizeCode($size))
            ->values();

        if ($sizes->isEmpty() || $sizes->count() > 15 || $sizes->contains(fn ($size) => mb_strlen($size) > 12)) {
            throw ValidationException::withMessages(['sizes' => 'اكتب من 1 إلى 15 مقاسًا، كل مقاس بحد أقصى 12 حرفًا، وافصل بينها بفاصلة.']);
        }

        return [
            'colors' => collect($colors)->map(fn ($color) => [
                'code' => $color['code'] ?? null,
                'name' => trim($color['name']),
                'hex' => strtolower($color['hex']),
            ])->all(),
            'sizes' => $sizes->all(),
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return preg_match('#^(https?:)?//#', $path) ? $path : asset($path);
    }

    private function isUploaded(?string $path): bool
    {
        return filled($path) && str_starts_with($path, 'storage/products/');
    }

    private function storagePath(string $path): string
    {
        return substr($path, strlen('storage/'));
    }

    private function log(Request $request, string $action, Product $product): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => 'المنتج '.$product->code,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['product_id' => $product->id, 'code' => $product->code],
        ]);
    }
}
