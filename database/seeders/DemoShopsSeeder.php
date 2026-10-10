<?php

namespace Database\Seeders;

use App\Models\BranchPricingRule;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PrintProvider;
use App\Models\PrintProviderBranch;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderNotifier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo print shops and orders in every state, to see how the admin and shop pages behave with more than one shop:
 * the suggested top three, an order a shop turned down, an order refused twice, and so on.
 *
 * Everything it makes is marked (demo.*@palprints.test accounts, DEMO-* order numbers) and `php artisan demo:shops-clean`
 * removes exactly that. The shops copy the settings (products, colours, sizes, print areas, prices) of the first approved
 * real shop, with different cities, prices and production times. Run again, it only fills in what is missing.
 */
class DemoShopsSeeder extends Seeder
{
    public const SHOP_EMAIL = 'demo.shop%@palprints.test';

    public const CUSTOMER_EMAIL = 'demo.customer%@palprints.test';

    public const ORDER_PREFIX = 'DEMO-';

    /** [company name, city, price factor, production-day shift]. */
    private const SHOPS = [
        ['مطبعة نابلس التجريبية', 'نابلس', 0.9, 0],
        ['مطبعة الخليل التجريبية', 'الخليل', 1.1, -1],
        ['مطبعة بيت لحم التجريبية', 'بيت لحم', 1.3, 2],
    ];

    /** [name, city] of the demo customers: their city decides which shop is "in the customer's city". */
    private const CUSTOMERS = [
        ['سارة (نابلس)', 'نابلس'],
        ['ليلى (الخليل)', 'الخليل'],
        ['محمد (غزة)', 'غزة'],
    ];

    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $source = $this->sourceBranch();

        DB::transaction(function () use ($source) {
            $branches = [];
            foreach (self::SHOPS as $index => [$name, $city, $factor, $shift]) {
                $branches[$index] = $this->shop($index + 1, $name, $city);
                $this->cloneOfferings($source, $branches[$index], $factor, $shift);
            }

            $customers = [];
            foreach (self::CUSTOMERS as $index => [$name, $city]) {
                $customers[$index] = [$this->user('demo.customer'.($index + 1).'@palprints.test', $name, 'customer'), $city];
            }

            $this->orders($source, $branches, $customers);
        });
    }

    /** Removes what this seeder made, and nothing else. */
    public static function clean(): array
    {
        return DB::transaction(function () {
            $orderIds = Order::where('order_number', 'like', self::ORDER_PREFIX.'%')->pluck('id');

            Notification::where('message', 'like', '%'.self::ORDER_PREFIX.'%')->delete();
            OrderStatusHistory::whereIn('order_id', $orderIds)->delete();
            Payment::whereIn('order_id', $orderIds)->delete();
            OrderItem::whereIn('order_id', $orderIds)->delete();
            $orders = Order::whereIn('id', $orderIds)->delete();

            // The accounts last: deleting a shop's account removes its branch and offerings with it.
            $users = User::where('email', 'like', self::SHOP_EMAIL)->orWhere('email', 'like', self::CUSTOMER_EMAIL)->get();
            $users->each->delete();

            return ['orders' => $orders, 'accounts' => $users->count()];
        });
    }

    /** The branch whose settings the demo shops copy: the approved real shop with the most products. */
    private function sourceBranch(): PrintProviderBranch
    {
        $branch = PrintProviderBranch::query()
            ->whereHas('printProvider', fn ($query) => $query->where('approval_status', 'approved')->where('is_active', true)
                ->whereHas('user', fn ($user) => $user->where('email', 'not like', self::SHOP_EMAIL)))
            ->withCount('branchProductOfferings')
            ->orderByDesc('branch_product_offerings_count')
            ->first();

        if (! $branch || $branch->branch_product_offerings_count === 0) {
            throw new \RuntimeException('There is no approved print shop with products to copy. Set one up first (its products, colours, sizes and prices).');
        }

        return $branch;
    }

    private function user(string $email, string $name, string $role): User
    {
        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name, 'password' => Hash::make('password'), 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $user->syncRoles([$role]);

        return $user;
    }

    private function shop(int $number, string $name, string $city): PrintProviderBranch
    {
        $owner = $this->user('demo.shop'.$number.'@palprints.test', $name, 'print_provider');

        $provider = PrintProvider::updateOrCreate(['user_id' => $owner->id], [
            'company_name' => $name, 'phone' => '059900000'.$number, 'address' => $city.'، شارع الجامعة',
            'approval_status' => 'approved', 'is_active' => true,
            'profile_completed_at' => now(), 'approved_at' => now(),
        ]);

        $branch = $provider->primaryBranch();
        $branch->update(['name' => 'فرع '.$city, 'city' => $city, 'region' => 'الضفة الغربية', 'address' => $city, 'phone' => $provider->phone, 'is_active' => true]);

        return $branch;
    }

    /** A copy of every product the source branch sells: variants, print areas, methods and prices, scaled. */
    private function cloneOfferings(PrintProviderBranch $source, PrintProviderBranch $target, float $factor, int $dayShift): void
    {
        $offerings = $source->branchProductOfferings()
            ->with(['branchOfferingVariants', 'branchPrintAreas.branchPrintCapabilities.branchPrintCapabilityVariants', 'branchPricingRules'])
            ->get();

        foreach ($offerings as $offering) {
            $copy = $target->branchProductOfferings()->updateOrCreate(['product_id' => $offering->product_id], [
                'base_price' => round((float) $offering->base_price * $factor, 2),
                'currency' => $offering->currency,
                'production_time_min' => max(1, (int) $offering->production_time_min + $dayShift),
                'production_time_max' => max(1, (int) $offering->production_time_max + $dayShift),
                'daily_capacity' => $offering->daily_capacity,
                'is_active' => $offering->is_active, // a product the real shop has switched off stays off here too
            ]);

            $variants = [];
            foreach ($offering->branchOfferingVariants as $variant) {
                $variants[$variant->id] = $copy->branchOfferingVariants()->updateOrCreate(['variant_id' => $variant->variant_id], [
                    'branch_sku' => $variant->branch_sku ? $variant->branch_sku.'-D'.$target->id : null,
                    'is_available' => $variant->is_available,
                ])->id;
            }

            $capabilities = [];
            foreach ($offering->branchPrintAreas as $area) {
                $areaCopy = $copy->branchPrintAreas()->updateOrCreate(['code' => $area->code], [
                    'name' => $area->name, 'max_width_mm' => $area->max_width_mm, 'max_height_mm' => $area->max_height_mm, 'is_active' => $area->is_active,
                ]);

                foreach ($area->branchPrintCapabilities as $capability) {
                    $capabilityCopy = $areaCopy->branchPrintCapabilities()->updateOrCreate(['printing_method_id' => $capability->printing_method_id], [
                        'applies_to_all_variants' => $capability->applies_to_all_variants,
                        'max_width_mm' => $capability->max_width_mm, 'max_height_mm' => $capability->max_height_mm, 'is_active' => $capability->is_active,
                    ]);
                    $capabilities[$capability->id] = $capabilityCopy->id;

                    foreach ($capability->branchPrintCapabilityVariants as $link) {
                        if (isset($variants[$link->branch_offering_variant_id])) {
                            $capabilityCopy->branchPrintCapabilityVariants()->firstOrCreate(['branch_offering_variant_id' => $variants[$link->branch_offering_variant_id]]);
                        }
                    }
                }
            }

            $copy->branchPricingRules()->delete();
            foreach ($offering->branchPricingRules as $rule) {
                BranchPricingRule::create([
                    'branch_product_offering_id' => $copy->id,
                    'branch_offering_variant_id' => $rule->branch_offering_variant_id ? ($variants[$rule->branch_offering_variant_id] ?? null) : null,
                    'branch_print_capability_id' => $rule->branch_print_capability_id ? ($capabilities[$rule->branch_print_capability_id] ?? null) : null,
                    'min_quantity' => $rule->min_quantity, 'max_quantity' => $rule->max_quantity,
                    'pricing_type' => $rule->pricing_type, 'value_type' => $rule->value_type,
                    'amount' => $rule->value_type === 'fixed' ? round((float) $rule->amount * $factor, 2) : $rule->amount,
                    'priority' => $rule->priority, 'is_active' => $rule->is_active,
                    'valid_from' => $rule->valid_from, 'valid_until' => $rule->valid_until,
                ]);
            }
        }
    }

    /**
     * @param  array<int, PrintProviderBranch>  $branches  the demo shops
     * @param  array<int, array{0: User, 1: string}>  $customers  [account, city]
     */
    private function orders(PrintProviderBranch $source, array $branches, array $customers): void
    {
        $shirt = $this->variantOf($source, 'tshirt-classic')
            ?? throw new \RuntimeException('The shop being copied does not sell an active t-shirt with colours and sizes; the demo orders need it.');
        // A second product for the orders that hold two: the first one the shop really sells.
        $mug = $this->variantOf($source, 'mug-ceramic') ?? $this->variantOf($source, 'hoodie-premium') ?? $this->variantOf($source, 'paper-print') ?? $shirt;
        $nour = $source;

        // [number, customer index, status, payment status, items, the branch that holds it, rejected-by branches, reason]
        $scenarios = [
            ['1001', 0, 'awaiting_payment_review', 'pending_review', [$shirt], $nour, [], null],
            ['1002', 1, 'awaiting_payment_review', 'pending_review', [$shirt, $mug], $nour, [], null],
            ['1003', 2, 'awaiting_payment_review', 'pending_review', [$shirt], $nour, [], null],
            ['1004', 0, 'processing', 'paid', [$shirt], $branches[0], [], null],
            ['1005', 1, 'confirmed', 'paid', [$shirt], $branches[1], [], null],
            ['1006', 0, 'ready', 'paid', [$shirt], $branches[0], [], null],
            ['1007', 2, 'shipped', 'paid', [$shirt], $branches[1], [], null],
            ['1008', 1, 'rejected', 'paid', [$shirt], $nour, [$nour], 'لا نملك الخامة المطلوبة حاليًا'],
            ['1009', 0, 'rejected', 'paid', [$shirt, $mug], $branches[0], [$nour, $branches[0]], 'المطبعة مشغولة بطلبات كبيرة'],
            ['1010', 2, 'delivered', 'paid', [$shirt], $branches[2], [], null],
            ['1011', 1, 'cancelled', 'paid', [$shirt], $branches[1], [], null],
        ];

        foreach ($scenarios as [$number, $customerIndex, $status, $paymentStatus, $variants, $holder, $rejectedBy, $reason]) {
            $orderNumber = self::ORDER_PREFIX.$number;
            if (Order::where('order_number', $orderNumber)->exists()) {
                continue;
            }

            [$customer, $city] = $customers[$customerIndex];
            $subtotal = 0;
            $items = [];
            foreach (collect($variants)->unique('product_id') as $variant) {
                $offering = $holder->branchProductOfferings()->where('product_id', $variant['product_id'])->first();
                $price = 30 + 5 * count($items);
                $subtotal += $price * 2;
                $items[] = [$variant, $offering, $price];
            }

            $order = Order::create([
                'user_id' => $customer->id, 'order_number' => $orderNumber, 'status' => $status,
                'payment_status' => $paymentStatus, 'payment_method' => 'bank',
                'subtotal' => $subtotal, 'shipping_cost' => 5, 'total_amount' => $subtotal + 5,
                'shipping_address_snapshot' => ['recipient_name' => $customer->name, 'phone' => '0599123456', 'city' => $city, 'street' => 'شارع تجريبي'],
                'notes' => 'طلب تجريبي لمعاينة الصفحات',
            ]);

            foreach ($items as [$variant, $offering, $price]) {
                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $variant['product_id'], 'variant_id' => $variant['variant_id'],
                    'print_provider_branch_id' => $holder->id, 'branch_product_offering_id' => $offering->id,
                    'quantity' => 2, 'unit_price' => $price, 'total_price' => $price * 2,
                ]);
            }

            Payment::create([
                'order_id' => $order->id, 'user_id' => $customer->id, 'payment_number' => 'DEMO-PAY-'.$number,
                'status' => $paymentStatus === 'paid' ? 'paid' : 'pending_review', 'method' => 'bank', 'amount' => $subtotal + 5,
                'paid_at' => $paymentStatus === 'paid' ? now() : null,
                'gateway_payload' => ['mode' => 'manual_receipt_review', 'receipt_original_name' => 'receipt-demo-'.$number.'.png'],
            ]);

            foreach ($rejectedBy as $branch) {
                OrderStatusHistory::create([
                    'order_id' => $order->id, 'changed_by' => $branch->printProvider->user_id, 'from_status' => 'processing', 'to_status' => 'rejected',
                    'note' => $reason, 'metadata' => ['rejected_branch_ids' => [$branch->id]],
                ]);
            }
            if ($rejectedBy) {
                OrderNotifier::rejectedByShop($orderNumber, (string) end($rejectedBy)->printProvider->company_name, (string) $reason);
            }
        }
    }

    /** @return array{product_id: int, variant_id: int}|null an available variant (colour + size) of a product the source branch really sells */
    private function variantOf(PrintProviderBranch $source, string $code): ?array
    {
        $product = Product::whereRaw('lower(code) = ?', [$code])->first();
        $offering = $product
            ? $source->branchProductOfferings()->where('product_id', $product->id)->where('is_active', true)->where('base_price', '>', 0)->first()
            : null;
        $variant = $offering?->branchOfferingVariants()->where('is_available', true)->orderBy('id')->first();

        return $variant ? ['product_id' => $product->id, 'variant_id' => $variant->variant_id] : null;
    }
}
