<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\BranchProductOffering;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryPartner;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PrintProvider;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Variant;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FullDemoScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        DB::transaction(function (): void {
            $admin = $this->user('demo.admin@palprints.test', 'Demo Admin', 'admin');
            $customer = $this->user('demo.customer@palprints.test', 'Demo Customer', 'customer');
            $designer = $this->user('demo.designer@palprints.test', 'Demo Designer', 'designer');
            $providerUser = $this->user('demo.provider@palprints.test', 'Demo Print Provider', 'print_provider');
            $deliveryUser = $this->user('demo.delivery@palprints.test', 'Demo Delivery Partner', 'delivery_partner');

            $this->profileDesigner($designer, $admin);
            $printProvider = $this->profilePrintProvider($providerUser, $admin);
            $deliveryPartner = $this->profileDeliveryPartner($deliveryUser, $admin);

            $this->call(CatalogDemoSeeder::class);

            $offering = BranchProductOffering::query()
                ->whereHas('product', fn ($query) => $query->where('code', 'TSHIRT-CLASSIC'))
                ->with(['product', 'printProviderBranch'])
                ->firstOrFail();

            $product = $offering->product;
            $variant = Variant::query()
                ->where('product_id', $product->id)
                ->where('sku', 'like', '%WHITE-S')
                ->first() ?? Variant::query()->where('product_id', $product->id)->firstOrFail();

            $design = Design::updateOrCreate(
                ['title' => 'Demo Palestine T-Shirt Design', 'designer_id' => $designer->id],
                [
                    'product_id' => $product->id,
                    'description' => 'Published demo design used to verify the full customer order workflow.',
                    'image' => 'front/assets/images/customer/products/1.png',
                    'status' => 'published',
                    'base_price' => 15,
                    'selling_price' => 25,
                    'designer_profit' => 6,
                    'selected_options' => [
                        'color' => 'white',
                        'size' => 'S',
                        'print_area' => 'front',
                    ],
                    'design_payload' => [
                        'canvas' => 'front',
                        'text' => 'PalPrints Demo',
                    ],
                    'rejection_reason' => null,
                    'submitted_at' => now()->subDays(3),
                    'reviewed_at' => now()->subDays(2),
                    'published_at' => now()->subDays(2),
                ],
            );

            $address = Address::updateOrCreate(
                ['user_id' => $customer->id, 'street' => 'Omar Al-Mukhtar Street'],
                [
                    'city' => 'Gaza',
                    'region' => 'Gaza Strip',
                    'building' => '12',
                    'apartment' => '4',
                    'phone' => '+970599111111',
                    'is_default' => true,
                    'is_deleted' => false,
                ],
            );

            $quantity = 2;
            $unitPrice = 25.00;
            $subtotal = $unitPrice * $quantity;
            $shippingCost = 10.00;
            $discountAmount = 0.00;
            $totalAmount = $subtotal + $shippingCost - $discountAmount;
            $providerCost = (float) $offering->base_price * $quantity;
            $designerProfit = 6.00 * $quantity;
            $platformCommission = $totalAmount - $providerCost - $designerProfit - $shippingCost;

            $cart = Cart::updateOrCreate(
                ['user_id' => $customer->id, 'status' => 'converted'],
                [
                    'converted_at' => now()->subDay(),
                    'abandoned_at' => null,
                ],
            );

            CartItem::updateOrCreate(
                [
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'design_id' => $design->id,
                ],
                [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'selected_options' => [
                        'color' => 'white',
                        'size' => 'S',
                        'print_area' => 'front',
                        'printing_method' => 'dtf',
                    ],
                ],
            );

            $order = Order::updateOrCreate(
                ['order_number' => 'DEMO-ORD-1001'],
                [
                    'user_id' => $customer->id,
                    'shipping_address_id' => $address->id,
                    'shipping_address_snapshot' => $address->only(['city', 'region', 'street', 'building', 'apartment', 'phone']),
                    'status' => 'shipped',
                    'payment_status' => 'paid',
                    'payment_method' => 'demo_card',
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'notes' => 'Demo order generated by FullDemoScenarioSeeder.',
                ],
            );

            OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'design_id' => $design->id,
                ],
                [
                    'designer_id' => $designer->id,
                    'print_provider_branch_id' => $offering->print_provider_branch_id,
                    'branch_product_offering_id' => $offering->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $subtotal,
                    'provider_cost' => $providerCost,
                    'designer_profit' => $designerProfit,
                    'platform_commission' => $platformCommission,
                    'selected_options' => [
                        'color' => 'white',
                        'size' => 'S',
                        'print_area' => 'front',
                        'printing_method' => 'dtf',
                    ],
                ],
            );

            Payment::updateOrCreate(
                ['payment_number' => 'DEMO-PAY-1001'],
                [
                    'order_id' => $order->id,
                    'user_id' => $customer->id,
                    'status' => 'paid',
                    'method' => 'demo_card',
                    'gateway' => 'demo_gateway',
                    'gateway_transaction_id' => 'DEMO-GW-TXN-1001',
                    'amount' => $totalAmount,
                    'currency' => 'ILS',
                    'paid_at' => now()->subDay(),
                    'refunded_at' => null,
                    'failure_reason' => null,
                    'gateway_payload' => [
                        'mode' => 'demo',
                        'approved' => true,
                    ],
                ],
            );

            foreach ([
                ['processing', null, now()->subDay()->subHours(2), 'Payment captured and order created.'],
                ['in_production', 'processing', now()->subDay()->subHour(), 'Assigned to print provider branch.'],
                ['ready', 'in_production', now()->subHours(12), 'Printed and packed.'],
                ['shipped', 'ready', now()->subHours(6), 'Handed to delivery partner.'],
            ] as [$toStatus, $fromStatus, $createdAt, $note]) {
                OrderStatusHistory::updateOrCreate(
                    ['order_id' => $order->id, 'to_status' => $toStatus],
                    [
                        'changed_by' => $admin->id,
                        'from_status' => $fromStatus,
                        'note' => $note,
                        'metadata' => ['source' => 'demo_seeder'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ],
                );
            }

            Shipment::updateOrCreate(
                ['shipment_number' => 'DEMO-SHP-1001'],
                [
                    'order_id' => $order->id,
                    'delivery_partner_id' => $deliveryPartner->id,
                    'tracking_number' => 'DEMO-TRACK-1001',
                    'status' => 'in_transit',
                    'shipping_cost' => $shippingCost,
                    'currency' => 'ILS',
                    'delivery_address_snapshot' => $address->only(['city', 'region', 'street', 'building', 'apartment', 'phone']),
                    'tracking_url' => 'https://example.test/track/DEMO-TRACK-1001',
                    'assigned_at' => now()->subHours(8),
                    'shipped_at' => now()->subHours(6),
                    'estimated_delivery_at' => now()->addDay(),
                    'delivered_at' => null,
                    'cancelled_at' => null,
                    'notes' => 'Demo shipment for database handoff.',
                ],
            );

            Review::updateOrCreate(
                ['user_id' => $customer->id, 'order_id' => $order->id],
                [
                    'rating' => 5,
                    'title' => 'Great platform experience',
                    'comment' => 'Demo platform review after a complete test order.',
                    'status' => 'approved',
                    'is_featured' => true,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'admin_notes' => 'Demo approved platform review.',
                    'metadata' => ['scope' => 'platform'],
                ],
            );

            $wallet = Wallet::updateOrCreate(
                ['user_id' => $designer->id],
                [
                    'total_balance' => $designerProfit,
                    'available_balance' => 0,
                    'pending_balance' => $designerProfit,
                    'total_withdrawn' => 0,
                    'last_updated' => now(),
                ],
            );

            WalletTransaction::updateOrCreate(
                ['wallet_id' => $wallet->id, 'reference_id' => 'DEMO-WALLET-ORDER-1001'],
                [
                    'order_id' => $order->id,
                    'type' => 'earning',
                    'amount' => $designerProfit,
                    'balance_after' => $designerProfit,
                    'description' => 'Pending designer profit from demo order DEMO-ORD-1001.',
                    'status' => 'pending',
                ],
            );
        });
    }

    private function user(string $email, string $name, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => '+970599000000',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'locale' => 'ar',
            ],
        );

        $user->syncRoles([$role]);

        return $user;
    }

    private function profileDesigner(User $designer, User $admin): void
    {
        DesignerProfile::updateOrCreate(
            ['user_id' => $designer->id],
            [
                'full_name' => 'Demo Designer',
                'bio' => 'Demo designer profile for database handoff.',
                'skills' => ['illustration', 'typography'],
                'portfolio_url' => 'https://example.test/designer',
                'profile_image' => null,
                'approval_status' => 'approved',
                'approved_at' => now()->subDays(5),
                'approved_by' => $admin->id,
                'rejection_reason' => null,
                'total_sales' => 1,
                'total_earnings' => 12,
                'profile_completed_at' => now()->subDays(6),
                'submitted_at' => now()->subDays(6),
                'reviewed_at' => now()->subDays(5),
                'admin_notes' => 'Approved demo designer.',
            ],
        );
    }

    private function profilePrintProvider(User $providerUser, User $admin): PrintProvider
    {
        return PrintProvider::updateOrCreate(
            ['user_id' => $providerUser->id],
            [
                'company_name' => 'Demo Print House',
                'address' => 'Gaza',
                'phone' => '+970599222222',
                'whatsapp_number' => '+970599222222',
                'working_hours' => ['sun_thu' => '09:00-17:00'],
                'license_document' => 'demo/license.pdf',
                'verification_document' => 'demo/verification.pdf',
                'approval_status' => 'approved',
                'approved_at' => now()->subDays(5),
                'approved_by' => $admin->id,
                'rejection_reason' => null,
                'total_orders' => 1,
                'total_earnings' => 30,
                'is_active' => true,
                'profile_completed_at' => now()->subDays(6),
                'submitted_at' => now()->subDays(6),
                'reviewed_at' => now()->subDays(5),
                'admin_notes' => 'Approved demo print provider.',
            ],
        );
    }

    private function profileDeliveryPartner(User $deliveryUser, User $admin): DeliveryPartner
    {
        return DeliveryPartner::updateOrCreate(
            ['user_id' => $deliveryUser->id],
            [
                'company_name' => 'Demo Delivery Co.',
                'contact_person' => 'Demo Courier Manager',
                'phone' => '+970599333333',
                'email' => 'demo.delivery@palprints.test',
                'service_areas' => ['Gaza', 'Ramallah'],
                'delivery_fee' => 10,
                'estimated_delivery_days' => 2,
                'api_key' => 'demo-delivery-api-key',
                'approval_status' => 'approved',
                'profile_completed_at' => now()->subDays(6),
                'submitted_at' => now()->subDays(6),
                'reviewed_at' => now()->subDays(5),
                'approved_at' => now()->subDays(5),
                'approved_by' => $admin->id,
                'rejection_reason' => null,
                'admin_notes' => 'Approved demo delivery partner.',
                'is_active' => true,
            ],
        );
    }
}