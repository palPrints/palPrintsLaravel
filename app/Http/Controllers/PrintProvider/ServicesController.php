<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\BranchPricingRule;
use App\Models\BranchPrintArea;
use App\Models\BranchPrintCapability;
use App\Models\BranchProductOffering;
use App\Models\PrintProvider;
use App\Models\PrintingMethod;
use App\Models\PrintProviderBranch;
use App\Models\Product;
use App\Models\Variant;
use App\Support\CatalogProductData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** The shop's products page: its offerings (price, production time, capacity, colours and sizes) kept in the database. */
class ServicesController extends Controller
{
    /** Printing methods that suit each product. Products not listed here may use every active method. */
    private const METHODS_BY_PRODUCT = [
        'tshirt-classic' => ['dtf', 'dtg', 'embroidery'],
        'HOODIE-PREMIUM' => ['dtf', 'dtg', 'embroidery'],
        'cap-classic' => ['dtf', 'embroidery'],
        'mug-ceramic' => ['sublimation'],
        'PAPER-PRINT' => ['digital-paper'],
        'STICKER-CUSTOM' => ['digital-paper', 'vinyl-cut'],
    ];

    public function index(Request $request): View
    {
        $provider = $this->provider($request);
        $branch = $provider->branches()->orderBy('id')->first();

        return view('printProvider.services', [
            'servicesData' => [
                'products' => $this->catalog($branch),
                'saveUrl' => route('print-provider.services.save', ['product' => '__ID__']),
                'toggleUrl' => route('print-provider.services.toggle', ['product' => '__ID__']),
            ],
        ]);
    }

    public function save(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $variants = $this->variants($product);
        $colorIds = $this->options($variants, 'color')->pluck('id')->all();
        $sizeIds = $this->options($variants, 'size')->pluck('id')->all();
        $methods = $this->methods($product);

        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0.01', 'max:1000000', 'decimal:0,2'],
            'days' => ['required', 'integer', 'min:1', 'max:365'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'colors' => ['required', 'array', 'min:1'],
            'colors.*' => ['string', 'in:'.implode(',', $colorIds ?: ['-'])],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*' => ['string', 'in:'.implode(',', $sizeIds ?: ['-'])],
            'methods' => ['required', 'array', 'min:1'],
            'methods.*.id' => ['required', 'integer', 'in:'.$methods->pluck('id')->implode(',')],
            'methods.*.price' => ['required', 'numeric', 'min:0', 'max:100000', 'decimal:0,2'],
            'methods.*.rate' => ['nullable', 'numeric', 'min:0', 'max:1000', 'decimal:0,2'],
        ], [
            'price.required' => 'أدخل سعر الطباعة.',
            'price.min' => 'يجب أن يكون السعر أكبر من صفر.',
            'price.decimal' => 'السعر يحتمل خانتين عشريتين كحد أقصى.',
            'days.required' => 'أدخل مدة الإنتاج.',
            'capacity.required' => 'أدخل السعة الإنتاجية اليومية.',
            'colors.required' => 'اختر لونًا واحدًا على الأقل.',
            'sizes.required' => 'اختر مقاسًا واحدًا على الأقل.',
            'methods.required' => 'اختر طريقة طباعة واحدة على الأقل.',
            'methods.min' => 'اختر طريقة طباعة واحدة على الأقل.',
            'methods.*.price.required' => 'أدخل سعر كل طريقة طباعة مختارة.',
            'methods.*.price.min' => 'سعر طريقة الطباعة لا يمكن أن يكون سالبًا.',
            'methods.*.price.decimal' => 'سعر طريقة الطباعة يحتمل خانتين عشريتين كحد أقصى.',
            'methods.*.rate.min' => 'السعر لكل 100 سم² لا يمكن أن يكون سالبًا.',
            'methods.*.rate.max' => 'السعر لكل 100 سم² كبير جدًا.',
            'methods.*.rate.decimal' => 'السعر لكل 100 سم² يحتمل خانتين عشريتين كحد أقصى.',
        ]);

        $branch = $this->provider($request)->primaryBranch();

        DB::transaction(function () use ($branch, $product, $data, $variants) {
            $offering = BranchProductOffering::firstOrNew([
                'print_provider_branch_id' => $branch->id,
                'product_id' => $product->id,
            ]);

            // A product that was only ticked in the profile (no price yet) goes live once its settings are saved.
            $placeholder = ! $offering->exists || (float) $offering->base_price <= 0;

            $offering->fill([
                'base_price' => $data['price'],
                'currency' => $offering->currency ?: 'ILS',
                'production_time_min' => $data['days'],
                'production_time_max' => $data['days'],
                'daily_capacity' => $data['capacity'],
                'is_active' => $placeholder ? true : $offering->is_active,
            ])->save();

            // A variant (colour + size) is offered when both of its choices are ticked.
            foreach ($variants as $variant) {
                $offering->branchOfferingVariants()->updateOrCreate(
                    ['variant_id' => $variant['id']],
                    ['is_available' => in_array($variant['color'], $data['colors'], true) && in_array($variant['size'], $data['sizes'], true)],
                );
            }

            $this->saveBasePrice($offering, (float) $data['price']);
            $this->savePrinting($offering, $product, collect($data['methods']));
        });

        return response()->json([
            'message' => 'تم حفظ إعدادات المنتج.',
            'product' => $this->catalog($branch, $product->id)->first(),
        ]);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $branch = $this->provider($request)->branches()->orderBy('id')->first();
        $offering = $branch?->branchProductOfferings()->where('product_id', $product->id)->first();

        abort_unless($offering, 404);

        if (! $offering->is_active && (float) $offering->base_price <= 0) {
            return response()->json(['message' => 'أكمل إعدادات المنتج (السعر والمدة والسعة) قبل تشغيله.'], 422);
        }

        $offering->update(['is_active' => ! $offering->is_active]);

        return response()->json([
            'message' => $offering->is_active ? 'تم تشغيل المنتج.' : 'تم إيقاف المنتج مؤقتًا.',
            'product' => $this->catalog($branch, $product->id)->first(),
        ]);
    }

    /** The base price also lives as the offering's own "base" pricing rule, like the rest of the pricing. */
    private function saveBasePrice(BranchProductOffering $offering, float $price): void
    {
        BranchPricingRule::updateOrCreate(
            ['branch_product_offering_id' => $offering->id, 'branch_offering_variant_id' => null, 'branch_print_capability_id' => null, 'pricing_type' => 'base'],
            ['min_quantity' => 1, 'value_type' => 'fixed', 'amount' => $price, 'priority' => 10, 'is_active' => true],
        );
    }

    /**
     * Every print area of the product gets a capability per chosen method, priced by one add-on rule. Methods that are no
     * longer chosen are switched off, not deleted.
     *
     * @param  Collection<int, array{id: int, price: float|string}>  $chosen
     */
    private function savePrinting(BranchProductOffering $offering, Product $product, Collection $chosen): void
    {
        $prices = $chosen->mapWithKeys(fn (array $method) => [(int) $method['id'] => (float) $method['price']]);
        $rates = $chosen->mapWithKeys(fn (array $method) => [(int) $method['id'] => (float) ($method['rate'] ?? 0)]);

        foreach (CatalogProductData::areaDefinitions($product->code) as $definition) {
            $area = BranchPrintArea::updateOrCreate(
                ['branch_product_offering_id' => $offering->id, 'code' => $definition['code']],
                ['name' => $definition['name'], 'max_width_mm' => $definition['width'], 'max_height_mm' => $definition['height'], 'is_active' => true],
            );

            foreach ($area->branchPrintCapabilities()->get() as $capability) {
                if (! $prices->has($capability->printing_method_id)) {
                    $capability->update(['is_active' => false]);
                    $capability->branchPricingRules()->update(['is_active' => false]);
                }
            }

            foreach ($prices as $methodId => $price) {
                $capability = BranchPrintCapability::updateOrCreate(
                    ['branch_print_area_id' => $area->id, 'printing_method_id' => $methodId],
                    ['applies_to_all_variants' => true, 'max_width_mm' => $definition['width'], 'max_height_mm' => $definition['height'], 'is_active' => true],
                );

                BranchPricingRule::updateOrCreate(
                    ['branch_product_offering_id' => $offering->id, 'branch_print_capability_id' => $capability->id, 'pricing_type' => 'print_method_addon'],
                    ['branch_offering_variant_id' => null, 'min_quantity' => 1, 'value_type' => 'fixed', 'amount' => $price, 'priority' => 20, 'is_active' => true],
                );

                // Price per 100 cm² of design (the design's bounding box), charged on top of the fixed add-on.
                BranchPricingRule::updateOrCreate(
                    ['branch_product_offering_id' => $offering->id, 'branch_print_capability_id' => $capability->id, 'pricing_type' => 'print_area_rate'],
                    ['branch_offering_variant_id' => null, 'min_quantity' => 1, 'value_type' => 'per_100cm2', 'amount' => $rates[$methodId] ?? 0, 'priority' => 25, 'is_active' => true],
                );
            }
        }
    }

    /** @return Collection<int, array{id: int, code: string, name: string}> */
    private function methods(Product $product): Collection
    {
        $allowed = self::METHODS_BY_PRODUCT[$product->code] ?? null;

        return PrintingMethod::query()
            ->where('is_active', true)
            ->when($allowed, fn ($query) => $query->whereIn('code', $allowed))
            ->orderBy('id')
            ->get(['id', 'code', 'name'])
            ->map(fn (PrintingMethod $method) => ['id' => $method->id, 'code' => $method->code, 'name' => $method->name])
            ->values();
    }

    private function provider(Request $request): PrintProvider
    {
        return $request->user()->printProvider ?? abort(403);
    }

    /**
     * Every product on the platform with the shop's own settings for it (null when the shop does not offer it).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function catalog(?PrintProviderBranch $branch, ?int $onlyProductId = null): Collection
    {
        $offerings = $branch
            ? $branch->branchProductOfferings()->with(['branchOfferingVariants', 'branchPrintAreas.branchPrintCapabilities.branchPricingRules'])->get()->keyBy('product_id')
            : collect();

        return Product::query()
            ->with('category:id,slug,name')
            ->where('is_active', true)
            ->when($onlyProductId, fn ($query) => $query->whereKey($onlyProductId))
            ->orderBy('id')
            ->get()
            ->map(function (Product $product) use ($offerings) {
                $variants = $this->variants($product);
                $colors = $this->options($variants, 'color');
                $sizes = $this->options($variants, 'size');
                /** @var BranchProductOffering|null $offering */
                $offering = $offerings->get($product->id);
                $available = $offering ? $offering->branchOfferingVariants->where('is_available', true)->pluck('variant_id') : collect();
                $chosen = $variants->whereIn('id', $available);

                $settings = null;
                if ($offering) {
                    $configured = (float) $offering->base_price > 0;
                    $hasRows = $offering->branchOfferingVariants->isNotEmpty();
                    $settings = [
                        'active' => (bool) $offering->is_active,
                        'configured' => $configured,
                        'price' => $configured ? (float) $offering->base_price : null,
                        'days' => $configured ? (int) $offering->production_time_max : null,
                        'capacity' => $configured ? (int) $offering->daily_capacity : null,
                        // A shop that never chose options starts with everything ticked.
                        'colors' => $hasRows ? $chosen->pluck('color')->unique()->values()->all() : $colors->pluck('id')->all(),
                        'sizes' => $hasRows ? $chosen->pluck('size')->unique()->values()->all() : $sizes->pluck('id')->all(),
                        'methods' => $offering->branchPrintAreas
                            ->flatMap(fn ($area) => $area->branchPrintCapabilities)
                            ->where('is_active', true)
                            ->groupBy('printing_method_id')
                            ->map(fn ($capabilities, $methodId) => [
                                'id' => (int) $methodId,
                                'price' => (float) ($capabilities->flatMap(fn ($capability) => $capability->branchPricingRules)
                                    ->where('pricing_type', 'print_method_addon')->where('is_active', true)->first()?->amount ?? 0),
                                'rate' => (float) ($capabilities->flatMap(fn ($capability) => $capability->branchPricingRules)
                                    ->where('pricing_type', 'print_area_rate')->where('is_active', true)->first()?->amount ?? 0),
                            ])
                            ->values()->all(),
                    ];
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category ? CatalogProductData::categoryName($product->category->slug, $product->category->name) : 'منتجات',
                    'image' => filled($product->image) ? asset($product->image) : null,
                    'colors' => $colors->values()->all(),
                    'sizes' => $sizes->values()->all(),
                    'methods' => $this->methods($product)->all(),
                    'settings' => $settings,
                ];
            })
            ->values();
    }

    /**
     * The product's active variants as colour/size code pairs.
     *
     * @return Collection<int, array{id: int, color: string, size: string, colorLabel: string, sizeLabel: string}>
     */
    private function variants(Product $product): Collection
    {
        return Variant::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->with('values.productAttributeValue.attributeValue', 'values.productAttributeValue.productAttribute.attribute')
            ->get()
            ->map(function (Variant $variant) {
                $pick = fn (string $attribute) => $variant->values
                    ->first(fn ($value) => $value->productAttributeValue?->productAttribute?->attribute?->code === $attribute)
                    ?->productAttributeValue?->attributeValue;
                $color = $pick('color');
                $size = $pick('size');

                return $color && $size ? [
                    'id' => $variant->id,
                    'color' => $color->code, 'colorLabel' => $color->value,
                    'size' => $size->code, 'sizeLabel' => $size->value,
                ] : null;
            })
            ->filter()
            ->values();
    }

    /** @return Collection<int, array{id: string, label: string, value?: string}> */
    private function options(Collection $variants, string $axis): Collection
    {
        return $variants
            ->unique($axis)
            ->map(fn (array $variant) => array_filter([
                'id' => $variant[$axis],
                'label' => $variant[$axis.'Label'],
                'value' => $axis === 'color' ? CatalogProductData::swatch($variant[$axis]) : null,
            ]))
            ->values();
    }
}
