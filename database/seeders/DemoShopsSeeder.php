<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\BranchPricingRule;
use App\Models\BranchProductOffering;
use App\Models\DeliveryPartner;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignFavorite;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PrintFile;
use App\Models\PrintProvider;
use App\Models\PrintProviderBranch;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\StickerBundleLine;
use App\Models\StickerCategory;
use App\Models\StickerLibraryItem;
use App\Models\User;
use App\Models\Variant;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Stickers\StickerLayoutService;
use App\Services\Stickers\StickerPricingService;
use App\Services\Stickers\StickerProviderPricing;
use App\Services\Stickers\StickerSettingsService;
use App\Support\CatalogProductData;
use App\Support\OrderNotifier;
use App\Support\PlatformSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo marketplace: print shops in Gaza, Khan Younis, Deir al-Balah and West Bank cities (each selling its own set of
 * products at its own prices), customers, designers with their designs, delivery companies, a sticker library and orders
 * in every state (designs, the customer's own artwork and sticker sheets), with shipments, reviews and designer wallets.
 *
 * Everything it makes is marked (demo.shopN / demo.customerN / demo.designerN / demo.deliveryN@palprints.test accounts,
 * DEMO-* order numbers, demo-* sticker pictures) and `php artisan demo:shops-clean` removes exactly that. The shops copy
 * the settings (variants, print areas, methods, prices) of the approved real shops. Run again, it only fills in what is
 * missing. The pictures come from database/seeders/demo-assets, so the server needs no image library.
 */
class DemoShopsSeeder extends Seeder
{
    // "_" makes the number required, so the single demo.customer@ / demo.designer@ accounts of FullDemoScenarioSeeder never match.
    public const SHOP_EMAIL = 'demo.shop_%@palprints.test';

    public const CUSTOMER_EMAIL = 'demo.customer_%@palprints.test';

    public const DESIGNER_EMAIL = 'demo.designer_%@palprints.test';

    public const DELIVERY_EMAIL = 'demo.delivery_%@palprints.test';

    public const ORDER_PREFIX = 'DEMO-';

    /** FullDemoScenarioSeeder's order: it starts with DEMO- too but is not ours to remove. */
    private const OTHER_DEMO_ORDERS = 'DEMO-ORD-%';

    public const STICKER_PREFIX = 'stickers/library/demo-';

    public const DESIGN_FILES_DIR = 'designer-designs/demo';

    private const GAZA = 'قطاع غزة';

    private const WEST_BANK = 'الضفة الغربية';

    /** number => [company name, city, region, street, price factor, production-day shift, product codes it sells]. */
    private const SHOPS = [
        1 => ['مطبعة جبل النار', 'نابلس', self::WEST_BANK, 'شارع فيصل', 0.9, 0, ['TSHIRT-CLASSIC', 'HOODIE-PREMIUM', 'MUG-CERAMIC', 'STICKER-CUSTOM', 'PAPER-PRINT', 'POSTER-PRINT', 'NOTEBOOK-CUSTOM']],
        2 => ['مطبعة الخليل للملابس', 'الخليل', self::WEST_BANK, 'شارع عين سارة', 1.1, -1, ['TSHIRT-CLASSIC', 'HOODIE-PREMIUM', 'CAP-CLASSIC', 'TOTE-CANVAS', 'SCARF-CUSTOM']],
        3 => ['مطبعة المهد', 'بيت لحم', self::WEST_BANK, 'شارع المهد', 1.3, 2, ['MUG-CERAMIC', 'STICKER-CUSTOM', 'PAPER-PRINT', 'WEDDING-CARDS', 'POSTER-PRINT']],
        4 => ['مطبعة رام الله الرقمية', 'رام الله', self::WEST_BANK, 'شارع الإرسال', 1.15, 0, ['TSHIRT-CLASSIC', 'HOODIE-PREMIUM', 'MUG-CERAMIC', 'STICKER-CUSTOM', 'PAPER-PRINT', 'PHONE-CASE', 'NOTEBOOK-CUSTOM']],
        5 => ['مطبعة جنين للإعلان', 'جنين', self::WEST_BANK, 'شارع أبو بكر', 0.85, 1, ['TSHIRT-CLASSIC', 'PAPER-PRINT', 'POSTER-PRINT', 'STICKER-CUSTOM']],
        6 => ['مطبعة الرمال', 'غزة', self::GAZA, 'شارع عمر المختار', 1.0, 0, ['TSHIRT-CLASSIC', 'HOODIE-PREMIUM', 'MUG-CERAMIC', 'STICKER-CUSTOM', 'PAPER-PRINT', 'POSTER-PRINT']],
        7 => ['مطبعة الشجاعية للورق', 'غزة', self::GAZA, 'شارع صلاح الدين', 0.8, 1, ['PAPER-PRINT', 'POSTER-PRINT', 'WEDDING-CARDS', 'NOTEBOOK-CUSTOM', 'STICKER-CUSTOM']],
        8 => ['مطبعة خانيونس الحديثة', 'خانيونس', self::GAZA, 'شارع جمال عبد الناصر', 0.95, 1, ['TSHIRT-CLASSIC', 'HOODIE-PREMIUM', 'CAP-CLASSIC', 'TOTE-CANVAS', 'MUG-CERAMIC']],
        9 => ['مطبعة دير البلح', 'دير البلح', self::GAZA, 'شارع البحر', 0.9, 2, ['MUG-CERAMIC', 'PHONE-CASE', 'STICKER-CUSTOM', 'PAPER-PRINT', 'TSHIRT-CLASSIC']],
    ];

    /** number => [name, city, region, street]: the city decides which shop is "in the customer's city". */
    private const CUSTOMERS = [
        1 => ['سارة عودة', 'نابلس', self::WEST_BANK, 'شارع رفيديا'],
        2 => ['ليلى التميمي', 'الخليل', self::WEST_BANK, 'شارع السلام'],
        3 => ['محمد أبو ندى', 'غزة', self::GAZA, 'شارع الجلاء'],
        4 => ['هبة شراب', 'خانيونس', self::GAZA, 'حي الأمل'],
        5 => ['خالد أبو سمرة', 'دير البلح', self::GAZA, 'شارع المحطة'],
        6 => ['نور الحسيني', 'رام الله', self::WEST_BANK, 'شارع الطيرة'],
    ];

    /** number => [name, city, bio, skills]. */
    private const DESIGNERS = [
        1 => ['رنا الحلو', 'غزة', 'مصممة غرافيك من غزة، أرسم رموز فلسطين بألوان بسيطة تصلح للطباعة على الملابس.', ['رسم رقمي', 'رموز فلسطينية', 'تايبوغرافي']],
        2 => ['أحمد أبو شمالة', 'خانيونس', 'مصمم هويات بصرية، أحب الزيتون والمفتاح والقدس في تصاميمي.', ['هوية بصرية', 'رسم خطي']],
        3 => ['لينا خليل', 'نابلس', 'أصمم للأكواب والهدايا مستوحاة من التطريز الفلسطيني وصباحات البلد.', ['تطريز', 'تصاميم هدايا']],
        4 => ['يوسف عودة', 'رام الله', 'أصمم لطلاب الجامعات: هندسة وطب ومناسبات.', ['تصاميم طلابية', 'أيقونات']],
    ];

    /** key => [designer, product code, title, description, status, base price, selling price, designer profit]. Pictures: demo-assets/designs/{key}.png. */
    private const DESIGNS = [
        'gaza-heart' => [1, 'TSHIRT-CLASSIC', 'غزة في القلب', 'قلب أحمر يحمل اسم غزة.', 'published', 15, 45, 8],
        'watermelon' => [1, 'TSHIRT-CLASSIC', 'بطيخة فلسطين', 'شريحة بطيخ بألوان العلم.', 'published', 15, 45, 8],
        'keffiyeh' => [1, 'TSHIRT-CLASSIC', 'نقشة الكوفية', 'مربع من نقشة الكوفية الفلسطينية.', 'published', 15, 50, 10],
        'olive-branch' => [2, 'HOODIE-PREMIUM', 'زيتون بلادي', 'غصن زيتون مع ثمار.', 'published', 35, 85, 15],
        'key-return' => [2, 'HOODIE-PREMIUM', 'مفتاح العودة', 'مفتاح البيت القديم مع كلمة راجعين.', 'published', 35, 85, 15],
        'jerusalem' => [2, 'TSHIRT-CLASSIC', 'القدس', 'قبة الصخرة بالأزرق والذهبي.', 'published', 15, 50, 10],
        'tatreez' => [3, 'MUG-CERAMIC', 'تطريز فلسطيني', 'نقشة تطريز حمراء تلف الكوب.', 'published', 12, 30, 6],
        'morning-coffee' => [3, 'MUG-CERAMIC', 'قهوة الصباح', 'فنجان قهوة وبخار للصبحيات.', 'published', 12, 30, 6],
        'good-morning' => [3, 'MUG-CERAMIC', 'صباح الخير', 'شمس تشرق على الكوب.', 'rejected', 12, 30, 6],
        'engineer' => [4, 'TSHIRT-CLASSIC', 'مهندس المستقبل', 'ترس هندسي لطلاب الهندسة.', 'published', 15, 45, 8],
        'future-doctor' => [4, 'HOODIE-PREMIUM', 'طبيبة المستقبل', 'نبض قلب لطالبات الطب.', 'draft', 35, 80, 12],
        'ramadan-night' => [4, 'HOODIE-PREMIUM', 'رمضان كريم', 'هلال ونجوم لليالي رمضان.', 'review', 35, 80, 12],
    ];

    /** Sticker category slug => [name, sort order]. */
    private const STICKER_CATEGORIES = [
        'palestine' => ['فلسطين', 5],
        'engineering' => ['هندسة', 10],
        'medicine' => ['طب', 20],
        'random' => ['عشوائيات', 30],
    ];

    /** key => [category slug, name]. Pictures: demo-assets/stickers/{key}.png. */
    private const STICKERS = [
        'watermelon' => ['palestine', 'بطيخة'], 'olive' => ['palestine', 'غصن زيتون'], 'key' => ['palestine', 'مفتاح العودة'], 'gaza-heart' => ['palestine', 'غزة في القلب'],
        'gear' => ['engineering', 'ترس'], 'circuit' => ['engineering', 'دائرة كهربائية'], 'set-square' => ['engineering', 'مثلث رسم'],
        'heartbeat' => ['medicine', 'نبض'], 'capsule' => ['medicine', 'كبسولة'], 'tooth' => ['medicine', 'سن'],
        'coffee' => ['random', 'قهوة'], 'cat' => ['random', 'قطة'], 'star' => ['random', 'نجمة'], 'smiley' => ['random', 'وجه مبتسم'],
    ];

    /** number => [company, contact person, service cities]. */
    private const DELIVERY = [
        1 => ['توصيل القطاع السريع', 'سامي النجار', ['غزة', 'خانيونس', 'دير البلح', 'رفح']],
        2 => ['بريد الضفة', 'ريم عساف', ['نابلس', 'رام الله', 'الخليل', 'بيت لحم', 'جنين']],
    ];

    /** The order steps, in order. An order holds every step up to its status (rejected / cancelled branch off "processing"). */
    private const STEPS = ['awaiting_payment_review', 'processing', 'confirmed', 'in_production', 'ready', 'shipped', 'delivered'];

    /** @var array<int, PrintProviderBranch> */
    private array $branches = [];

    /** @var array<int, array{0: User, 1: Address}> */
    private array $customers = [];

    /** @var array<string, Design> */
    private array $designs = [];

    /** @var array<string, StickerLibraryItem> */
    private array $stickers = [];

    /** @var array<int, DeliveryPartner> */
    private array $delivery = [];

    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $source = $this->sourceOfferings();

        DB::transaction(function () use ($source) {
            foreach (self::SHOPS as $number => [$name, $city, $region, $street, $factor, $shift, $products]) {
                $this->branches[$number] = $this->shop($number, $name, $city, $region, $street);
                $this->offerProducts($source, $this->branches[$number], $products, $factor, $shift);
            }

            foreach (self::CUSTOMERS as $number => [$name, $city, $region, $street]) {
                $this->customers[$number] = $this->customer($number, $name, $city, $region, $street);
            }

            foreach (self::DELIVERY as $number => [$company, $contact, $cities]) {
                $this->delivery[$number] = $this->deliveryPartner($number, $company, $contact, $cities);
            }

            $designers = [];
            foreach (self::DESIGNERS as $number => [$name, $city, $bio, $skills]) {
                $designers[$number] = $this->designer($number, $name, $bio, $skills);
            }
            foreach (self::DESIGNS as $key => $design) {
                if ($made = $this->design($key, $designers[$design[0]], ...array_slice($design, 1))) {
                    $this->designs[$key] = $made;
                }
            }

            $this->stickerLibrary();
            $this->orders();
            $this->favorites();
            $this->wallets($designers);
        });
    }

    /** Removes what this seeder made, and nothing else. */
    public static function clean(): array
    {
        return DB::transaction(function () {
            $orderIds = Order::where('order_number', 'like', self::ORDER_PREFIX.'%')
                ->where('order_number', 'not like', self::OTHER_DEMO_ORDERS)->pluck('id');
            $itemIds = OrderItem::whereIn('order_id', $orderIds)->pluck('id');

            Notification::where('message', 'like', '%'.self::ORDER_PREFIX.'%')->where('message', 'not like', '%DEMO-ORD-%')->delete();
            PrintFile::whereIn('order_item_id', $itemIds)->get()->each(function (PrintFile $file) {
                Storage::disk($file->disk ?: 'local')->delete($file->stored_path);
                $file->delete();
            });
            StickerBundleLine::whereIn('order_item_id', $itemIds)->delete();
            WalletTransaction::whereIn('order_id', $orderIds)->delete();
            Review::whereIn('order_id', $orderIds)->delete();
            Shipment::whereIn('order_id', $orderIds)->delete();
            OrderStatusHistory::whereIn('order_id', $orderIds)->delete();
            Payment::whereIn('order_id', $orderIds)->delete();
            OrderItem::whereIn('id', $itemIds)->delete();
            $orders = Order::whereIn('id', $orderIds)->delete();

            $users = User::where(fn ($query) => collect([self::SHOP_EMAIL, self::CUSTOMER_EMAIL, self::DESIGNER_EMAIL, self::DELIVERY_EMAIL])
                ->each(fn ($pattern) => $query->orWhere('email', 'like', $pattern)))->get();

            $designIds = Design::whereIn('designer_id', $users->pluck('id'))->pluck('id');
            DesignFavorite::whereIn('design_id', $designIds)->delete();
            Design::whereIn('id', $designIds)->delete();
            Storage::disk('local')->deleteDirectory(self::DESIGN_FILES_DIR);

            // A demo sticker a real customer has ordered stays (switched off), so their order keeps its picture.
            $stickers = StickerLibraryItem::where('image_path', 'like', self::STICKER_PREFIX.'%')->get();
            $stickers->each(function (StickerLibraryItem $item) {
                if (StickerBundleLine::where('sticker_library_item_id', $item->id)->exists()) {
                    $item->update(['is_active' => false]);

                    return;
                }
                Storage::disk($item->disk ?: 'public')->delete($item->image_path);
                $item->delete();
            });
            StickerCategory::where('slug', 'palestine')->doesntHave('stickers')->delete();

            // The accounts last: deleting one removes its shop, branch, offerings, profile, wallet and addresses with it.
            $users->each(fn (User $user) => $user->wallet()->exists() ? WalletTransaction::where('wallet_id', $user->wallet->id)->delete() : null);
            $users->each->delete();

            return ['orders' => $orders, 'accounts' => $users->count(), 'designs' => $designIds->count(), 'stickers' => $stickers->count()];
        });
    }

    /**
     * What the demo shops copy, by product: the offering of the approved real shop with the most products, and for a
     * product it does not sell, the next shop's.
     *
     * @return Collection<int, BranchProductOffering>
     */
    private function sourceOfferings(): Collection
    {
        $branches = PrintProviderBranch::query()
            ->whereHas('printProvider', fn ($query) => $query->where('approval_status', 'approved')->where('is_active', true)
                ->whereHas('user', fn ($user) => $user->where('email', 'not like', self::SHOP_EMAIL)))
            ->withCount('branchProductOfferings')
            ->orderByDesc('branch_product_offerings_count')
            ->get();

        $offerings = collect();
        foreach ($branches as $branch) {
            $branch->branchProductOfferings()
                ->with(['product', 'branchOfferingVariants', 'branchPrintAreas.branchPrintCapabilities.branchPrintCapabilityVariants', 'branchPricingRules'])
                ->get()
                ->each(fn (BranchProductOffering $offering) => $offerings->has($offering->product_id) ? null : $offerings->put($offering->product_id, $offering));
        }

        if ($offerings->isEmpty()) {
            throw new \RuntimeException('There is no approved print shop with products to copy. Set one up first (its products, colours, sizes and prices).');
        }

        return $offerings;
    }

    private function user(string $email, string $name, string $role, string $phone): User
    {
        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name, 'phone' => $phone, 'password' => Hash::make((string) config('demo.password', 'password')),
            'is_active' => true, 'email_verified_at' => now(), 'locale' => 'ar',
        ]);
        $user->syncRoles([$role]);

        return $user;
    }

    private function shop(int $number, string $name, string $city, string $region, string $street): PrintProviderBranch
    {
        $phone = sprintf('0599%06d', 100 + $number);
        $owner = $this->user('demo.shop'.$number.'@palprints.test', $name, 'print_provider', $phone);

        $provider = PrintProvider::updateOrCreate(['user_id' => $owner->id], [
            'company_name' => $name, 'phone' => $phone, 'address' => $city.'، '.$street,
            'working_hours' => ['sat_thu' => '08:30-18:00'],
            'approval_status' => 'approved', 'is_active' => true,
            'profile_completed_at' => now()->subWeeks(3), 'submitted_at' => now()->subWeeks(3), 'reviewed_at' => now()->subWeeks(2), 'approved_at' => now()->subWeeks(2),
        ]);

        $branch = $provider->primaryBranch();
        $branch->update(['name' => 'فرع '.$city, 'city' => $city, 'region' => $region, 'address' => $city.'، '.$street, 'phone' => $phone, 'is_active' => true]);

        return $branch;
    }

    /**
     * The shop sells exactly $codes: a copy of each source offering (variants, print areas, methods and prices, scaled).
     * What it sold before and is not in the list any more is switched off, not deleted, so old orders keep their line.
     */
    private function offerProducts(Collection $source, PrintProviderBranch $target, array $codes, float $factor, int $dayShift): void
    {
        $offerings = $source->filter(fn (BranchProductOffering $offering) => in_array(strtoupper((string) $offering->product?->code), $codes, true));
        $target->branchProductOfferings()->whereNotIn('product_id', $offerings->keys())->update(['is_active' => false]);

        foreach ($offerings as $offering) {
            $copy = $target->branchProductOfferings()->updateOrCreate(['product_id' => $offering->product_id], [
                'base_price' => round((float) $offering->base_price * $factor, 2),
                'currency' => $offering->currency,
                'production_time_min' => max(1, (int) $offering->production_time_min + $dayShift),
                'production_time_max' => max(1, (int) $offering->production_time_max + $dayShift),
                'daily_capacity' => $offering->daily_capacity,
                'sticker_materials' => $offering->sticker_materials,
                'is_active' => true,
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

    /** @return array{0: User, 1: Address} */
    private function customer(int $number, string $name, string $city, string $region, string $street): array
    {
        $phone = sprintf('0599%06d', 200 + $number);
        $user = $this->user('demo.customer'.$number.'@palprints.test', $name, 'customer', $phone);
        $address = Address::updateOrCreate(['user_id' => $user->id, 'street' => $street], [
            'city' => $city, 'region' => $region, 'building' => (string) (10 + $number), 'apartment' => (string) $number,
            'phone' => $phone, 'is_default' => true, 'is_deleted' => false,
        ]);

        return [$user, $address];
    }

    private function deliveryPartner(int $number, string $company, string $contact, array $cities): DeliveryPartner
    {
        $phone = sprintf('0599%06d', 300 + $number);
        $email = 'demo.delivery'.$number.'@palprints.test';
        $user = $this->user($email, $company, 'delivery_partner', $phone);

        return DeliveryPartner::updateOrCreate(['user_id' => $user->id], [
            'company_name' => $company, 'contact_person' => $contact, 'phone' => $phone, 'email' => $email,
            'service_areas' => $cities, 'delivery_fee' => 5, 'estimated_delivery_days' => 2,
            'approval_status' => 'approved', 'is_active' => true,
            'profile_completed_at' => now()->subWeeks(3), 'submitted_at' => now()->subWeeks(3), 'reviewed_at' => now()->subWeeks(2), 'approved_at' => now()->subWeeks(2),
        ]);
    }

    private function designer(int $number, string $name, string $bio, array $skills): User
    {
        $user = $this->user('demo.designer'.$number.'@palprints.test', $name, 'designer', sprintf('0599%06d', 400 + $number));
        DesignerProfile::updateOrCreate(['user_id' => $user->id], [
            'full_name' => $name, 'bio' => $bio, 'skills' => $skills, 'approval_status' => 'approved',
            'profile_completed_at' => now()->subWeeks(4), 'submitted_at' => now()->subWeeks(4), 'reviewed_at' => now()->subWeeks(3), 'approved_at' => now()->subWeeks(3),
        ]);

        return $user;
    }

    /** The design, with its print-ready picture saved privately like the studio saves it (the shop prints from it). */
    private function design(string $key, User $designer, string $productCode, string $title, string $description, string $status, int $base, int $selling, int $profit): ?Design
    {
        $product = Product::whereRaw('upper(code) = ?', [$productCode])->first();
        if (! $product) {
            return null; // a site without this product gets no design for it
        }
        $printFile = self::DESIGN_FILES_DIR.'/'.$key.'.png';
        $disk = Storage::disk('local');
        if (! $disk->exists($printFile)) {
            $disk->put($printFile, file_get_contents(database_path('seeders/demo-assets/designs/'.$key.'.png')));
        }

        $submitted = now()->subDays(20 - crc32($key) % 10);

        return Design::updateOrCreate(['designer_id' => $designer->id, 'title' => $title], [
            'product_id' => $product->id,
            'description' => $description,
            'image' => 'front/assets/images/demo/designs/'.$key.'.png',
            'status' => $status,
            'base_price' => $base,
            'selling_price' => $selling,
            'designer_profit' => $profit,
            // What the studio saves: the colours it may be printed on, and for clothes the audience and its sizes.
            'selected_options' => CatalogProductData::isApparel($productCode)
                ? ['color_id' => 'white', 'allowed_color_ids' => ['white', 'gray'], 'display_category' => 'adults', 'allowed_size_ids' => ['S', 'M', 'L', 'XL']]
                : ['color_id' => 'white', 'allowed_color_ids' => ['white']],
            'design_payload' => [
                'product_code' => $productCode,
                'demo' => true,
                'print_exports' => [['area_id' => 'front', 'name' => 'الأمام', 'path' => $printFile, 'size' => $disk->size($printFile)]],
            ],
            'rejection_reason' => $status === 'rejected' ? 'الألوان فاتحة على الكوب الأبيض، جرّب درجة برتقالي أغمق.' : null,
            'submitted_at' => $status === 'draft' ? null : $submitted,
            'reviewed_at' => in_array($status, ['published', 'rejected'], true) ? $submitted->copy()->addDay() : null,
            'published_at' => $status === 'published' ? $submitted->copy()->addDay() : null,
        ]);
    }

    private function stickerLibrary(): void
    {
        $categories = [];
        foreach (self::STICKER_CATEGORIES as $slug => [$name, $sort]) {
            $categories[$slug] = StickerCategory::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true, 'sort_order' => $sort]);
        }

        $disk = Storage::disk('public');
        $sort = 0;
        foreach (self::STICKERS as $key => [$category, $name]) {
            $path = self::STICKER_PREFIX.$key.'.png';
            if (! $disk->exists($path)) {
                $disk->put($path, file_get_contents(database_path('seeders/demo-assets/stickers/'.$key.'.png')));
            }

            $this->stickers[$key] = StickerLibraryItem::updateOrCreate(['image_path' => $path], [
                'sticker_category_id' => $categories[$category]->id, 'name' => $name, 'disk' => 'public',
                'mime_type' => 'image/png', 'file_size' => $disk->size($path), 'is_active' => true, 'sort_order' => $sort += 10,
            ]);
        }
    }

    private function orders(): void
    {
        // [number, customer, shop, status, days ago, items, shops that turned it down, their reason, review [rating, comment]]
        // An item: ['design', key, quantity] | ['own', product code, quantity, artwork key] | ['stickers', [sticker key => quantity]]
        $scenarios = [
            ['1001', 3, 6, 'awaiting_payment_review', 0, [['design', 'gaza-heart', 2], ['design', 'watermelon', 1]]],
            ['1002', 1, 1, 'awaiting_payment_review', 1, [['stickers', ['watermelon' => 10, 'olive' => 10, 'key' => 10]]]],
            ['1003', 6, 4, 'processing', 1, [['design', 'jerusalem', 1], ['design', 'morning-coffee', 2]]],
            ['1004', 4, 8, 'processing', 2, [['design', 'olive-branch', 1]]],
            ['1005', 5, 9, 'confirmed', 3, [['design', 'tatreez', 3], ['stickers', ['coffee' => 12, 'cat' => 12]]]],
            ['1006', 2, 2, 'in_production', 4, [['design', 'key-return', 2], ['own', 'TSHIRT-CLASSIC', 5, 'watermelon']]],
            ['1007', 3, 6, 'ready', 5, [['design', 'keffiyeh', 1], ['stickers', ['gear' => 10, 'circuit' => 10, 'set-square' => 10]]]],
            ['1008', 1, 1, 'shipped', 6, [['design', 'engineer', 2]]],
            ['1009', 6, 4, 'delivered', 12, [['design', 'watermelon', 1], ['design', 'tatreez', 1]], [], null, [5, 'الطباعة نظيفة والألوان ثابتة بعد الغسيل.']],
            ['1010', 4, 8, 'delivered', 15, [['design', 'gaza-heart', 3]], [], null, [4, 'وصل الطلب بسرعة، المقاس أكبر بقليل من المتوقع.']],
            ['1011', 5, 9, 'delivered', 18, [['stickers', ['heartbeat' => 15, 'capsule' => 15, 'tooth' => 15]]], [], null, [5, 'ستيكرات رائعة لطلاب الطب، القص دقيق.']],
            ['1012', 2, 2, 'rejected', 7, [['design', 'olive-branch', 1]], [2], 'لا نملك الخامة المطلوبة حاليًا'],
            ['1013', 1, 5, 'rejected', 8, [['design', 'engineer', 1]], [1, 5], 'المطبعة مشغولة بطلبات كبيرة'],
            ['1014', 3, 6, 'cancelled', 9, [['design', 'jerusalem', 1]]],
            ['1015', 6, 3, 'processing', 0, [['design', 'morning-coffee', 4]]],
            ['1016', 4, 7, 'processing', 1, [['stickers', ['star' => 20, 'smiley' => 20]]]],
            ['1017', 2, 5, 'ready', 5, [['own', 'TSHIRT-CLASSIC', 3, 'gaza-heart']]],
            ['1018', 5, 8, 'in_production', 3, [['design', 'key-return', 1]]],
        ];

        foreach ($scenarios as $scenario) {
            [$number, $customer, $shop, $status, $daysAgo, $items] = $scenario;
            $orderNumber = self::ORDER_PREFIX.$number;
            if (Order::where('order_number', $orderNumber)->exists()) {
                continue;
            }

            $this->order($orderNumber, $this->customers[$customer], $this->branches[$shop], $status, now()->subDays($daysAgo)->setTime(10 + $daysAgo % 8, 15), $items,
                array_map(fn ($n) => $this->branches[$n], $scenario[6] ?? []), $scenario[7] ?? null, $scenario[8] ?? null);
        }
    }

    /**
     * @param  array{0: User, 1: Address}  $customer
     * @param  array<int, PrintProviderBranch>  $rejectedBy
     */
    private function order(string $orderNumber, array $customer, PrintProviderBranch $branch, string $status, Carbon $at, array $items, array $rejectedBy, ?string $reason, ?array $review): void
    {
        [$user, $address] = $customer;
        $paid = $status !== 'awaiting_payment_review';

        $order = Order::create([
            'user_id' => $user->id, 'order_number' => $orderNumber, 'status' => $status,
            'payment_status' => $paid ? 'paid' : 'pending_review', 'payment_method' => 'palpay',
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => ['recipient_name' => $user->name, 'phone' => $address->phone] + $address->only(['city', 'region', 'street', 'building', 'apartment']),
            'subtotal' => 0, 'shipping_cost' => 0, 'discount_amount' => 0, 'total_amount' => 0,
            'notes' => 'طلب تجريبي لمعاينة الموقع',
        ]);

        foreach ($items as $item) {
            $this->orderItem($order, $branch, $item);
        }

        $subtotal = (float) $order->items()->sum('total_price');
        if ($subtotal <= 0) {
            $order->delete(); // the shop sells none of it

            return;
        }
        $shipping = (float) PlatformSettings::get('fees', 'shipping_cost', 5);
        $order->forceFill(['subtotal' => $subtotal, 'shipping_cost' => $shipping, 'total_amount' => $subtotal + $shipping, 'created_at' => $at, 'updated_at' => $at])->save();

        Payment::create([
            'order_id' => $order->id, 'user_id' => $user->id, 'payment_number' => 'DEMO-SHOP-PAY-'.substr($orderNumber, strlen(self::ORDER_PREFIX)),
            'status' => $paid ? 'paid' : 'pending_review', 'method' => 'palpay', 'gateway' => 'palpay', 'amount' => $subtotal + $shipping, 'currency' => 'ILS',
            'paid_at' => $paid ? $at->copy()->addHours(2) : null,
            'gateway_payload' => ['mode' => 'manual_receipt_review', 'receipt_original_name' => 'receipt-'.$orderNumber.'.png'],
        ]);

        $this->history($order, $branch, $status, $at, $rejectedBy, $reason);

        if (in_array($status, ['shipped', 'delivered'], true)) {
            $this->shipment($order, $address, $status, $at);
        }

        if ($review) {
            Review::updateOrCreate(['user_id' => $user->id, 'order_id' => $order->id], [
                'rating' => $review[0], 'title' => $review[0] >= 5 ? 'تجربة ممتازة' : 'تجربة جيدة', 'comment' => $review[1],
                'status' => 'approved', 'is_featured' => $review[0] >= 5, 'reviewed_at' => now(), 'metadata' => ['scope' => 'platform'],
            ]);
        }
    }

    private function orderItem(Order $order, PrintProviderBranch $branch, array $item): void
    {
        if ($item[0] === 'design' && ! isset($this->designs[$item[1]])) {
            return;
        }
        $code = match ($item[0]) {
            'design' => strtoupper($this->designs[$item[1]]->product->code),
            'own' => $item[1],
            'stickers' => 'STICKER-CUSTOM',
        };
        $offering = $branch->branchProductOfferings()->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->whereRaw('upper(code) = ?', [$code]))->first();
        $branchVariant = $offering?->branchOfferingVariants()->where('is_available', true)
            ->orderByRaw("case when branch_sku like '%WHITE%' then 0 else 1 end")->orderBy('id')->first();
        if (! $branchVariant) {
            return;
        }
        $variant = Variant::with('values.productAttributeValue.attributeValue', 'values.productAttributeValue.productAttribute.attribute')->find($branchVariant->variant_id);
        $where = ['branch_product_offering_id' => $offering->id, 'print_provider_branch_id' => $branch->id];

        if ($item[0] === 'stickers') {
            $this->stickerItem($order, $offering, $variant, $item[1]);

            return;
        }

        $quantity = $item[2];
        $design = $item[0] === 'design' ? $this->designs[$item[1]] : null;
        $unit = $design ? (float) $design->selling_price : (float) $offering->base_price;
        $providerCost = (float) $offering->base_price * $quantity;
        $designerProfit = $design ? (float) $design->designer_profit * $quantity : 0;

        $orderItem = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $offering->product_id, 'variant_id' => $variant->id,
            'design_id' => $design?->id, 'designer_id' => $design?->designer_id,
            'item_type' => $design ? OrderItem::TYPE_CATALOG_DESIGN : OrderItem::TYPE_CUSTOMER_UPLOAD,
            'print_provider_branch_id' => $branch->id, 'branch_product_offering_id' => $offering->id,
            'quantity' => $quantity, 'unit_price' => $unit, 'total_price' => $unit * $quantity,
            'provider_cost' => $providerCost, 'designer_profit' => $designerProfit,
            'platform_commission' => max(0, $unit * $quantity - $providerCost - $designerProfit),
            'selected_options' => $this->variantLabels($variant) + [
                'print_areas' => ['front'], 'printing_method' => 'dtf', 'printing_method_name' => 'DTF',
                'design_name' => $design?->title ?? 'تصميمي الخاص',
            ] + $where,
        ]);

        // The picture the shop prints from: the design's print-ready file, or the customer's own artwork.
        $source = $design ? $design->design_payload['print_exports'][0]['path'] : null;
        $path = ($design ? PrintFile::EXPORT_DIR.$order->user_id.'/front--' : 'customer-print-files/orders/'.$order->id.'/').Str::uuid().'.png';
        $disk = Storage::disk('local');
        $disk->put($path, $source ? $disk->get($source) : file_get_contents(database_path('seeders/demo-assets/designs/'.$item[3].'.png')));
        $file = PrintFile::create([
            'user_id' => $order->user_id, 'product_id' => $offering->product_id, 'order_item_id' => $orderItem->id,
            'original_name' => $design ? 'print-ready-front.png' : 'my-design.png', 'stored_path' => $path, 'disk' => 'local',
            'mime_type' => 'image/png', 'extension' => 'png', 'file_size' => $disk->size($path), 'page_count' => 1,
            'status' => PrintFile::STATUS_ATTACHED_TO_ORDER, 'uploaded_at' => now(), 'attached_to_order_at' => now(),
        ]);

        if (! $design) {
            $orderItem->update(['selected_options' => $orderItem->selected_options + ['files' => [[
                'name' => $file->original_name, 'path' => $file->stored_path, 'disk' => 'local', 'size' => $file->file_size, 'page_count' => 1, 'mime_type' => 'image/png',
            ]]]]);
        }
    }

    /** A sheet of library stickers, laid out and priced by the same services the sticker page uses. */
    private function stickerItem(Order $order, BranchProductOffering $offering, Variant $variant, array $quantities): void
    {
        $settings = app(StickerSettingsService::class)->get();
        $settings['materials'] = $offering->sticker_materials ?: [
            'paper' => [['key' => 'photo-sticker', 'label' => 'فوتو لاصق', 'price_per_sheet' => 0, 'available' => true]],
            'protection' => ['label' => 'طبقة حماية', 'price_per_sheet' => 0, 'available' => false],
        ];
        $sizes = collect($settings['layout']['sizes'])->pluck('key');
        $size = $sizes->contains('5x5') ? '5x5' : $sizes->first();

        $input = [];
        foreach ($quantities as $key => $quantity) {
            $sticker = $this->stickers[$key];
            $input[] = [
                'client_id' => 'demo-'.$key, 'source' => StickerBundleLine::SOURCE_LIBRARY, 'library_item_id' => $sticker->id,
                'label' => $sticker->name, 'image_url' => $sticker->imageUrl(), 'quantity' => $quantity, 'size_key' => $size,
                'cut_type' => 'circle', 'image_width_px' => 500, 'image_height_px' => 500,
            ];
        }

        $layout = app(StickerLayoutService::class);
        $lines = $layout->normalizeLines($input, $settings);
        $quote = app(StickerPricingService::class)->quote($lines, array_merge($settings, ['page_specs' => []]));
        $snapshot = $layout->arrange($lines, $settings);
        $warnings = array_values(array_unique(array_merge($quote['warnings'], $layout->qualityWarnings($lines, $settings))));

        $options = [
            'design_name' => 'حزمة ملصقات A4', 'print_areas' => ['front'], 'printing_method' => 'vinyl-cut', 'printing_method_name' => 'Vinyl cut',
            'branch_product_offering_id' => $offering->id, 'print_provider_branch_id' => $offering->print_provider_branch_id,
            'sticker_quantity' => $quote['quantity'], 'page_specs' => [], 'sticker_page_count' => $snapshot['page_count'],
            'sticker_quote' => $quote, 'sticker_settings_snapshot' => $settings,
            'layout_snapshot' => array_merge($snapshot, ['pricing' => $quote, 'warnings' => $warnings]), 'warnings' => $warnings,
        ];
        $providerQuote = app(StickerProviderPricing::class)->quote($offering, $options);

        $orderItem = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $offering->product_id, 'variant_id' => $variant->id,
            'item_type' => OrderItem::TYPE_STICKER_BUNDLE,
            'print_provider_branch_id' => $offering->print_provider_branch_id, 'branch_product_offering_id' => $offering->id,
            'quantity' => 1, 'unit_price' => $quote['total'], 'total_price' => $quote['total'],
            'provider_cost' => $providerQuote['total'], 'designer_profit' => 0,
            'platform_commission' => max(0, $quote['total'] - $providerQuote['total']),
            'selected_options' => $options + ['provider_pricing' => $providerQuote, 'files' => []],
        ]);

        foreach ($lines as $line) {
            StickerBundleLine::create([
                'order_item_id' => $orderItem->id, 'source' => $line['source'], 'client_id' => $line['client_id'],
                'sticker_library_item_id' => $line['library_item_id'], 'quantity' => $line['quantity'], 'size_key' => $line['size_key'],
                'width_mm' => $line['width_mm'], 'height_mm' => $line['height_mm'], 'cut_type' => $line['cut_type'],
                'label' => $line['label'], 'sort_order' => $line['sort_order'],
            ]);
        }
    }

    /** @return array{color?: string, size?: string} */
    private function variantLabels(Variant $variant): array
    {
        $labels = [];
        foreach ($variant->values as $value) {
            $code = $value->productAttributeValue?->productAttribute?->attribute?->code;
            if (in_array($code, ['color', 'size'], true)) {
                $labels[$code] = (string) $value->productAttributeValue->attributeValue?->value;
            }
        }

        return $labels;
    }

    /** Every step up to the order's status, with the notifications the real flow sends. */
    private function history(Order $order, PrintProviderBranch $branch, string $status, Carbon $at, array $rejectedBy, ?string $reason): void
    {
        $customerId = $order->user_id;
        $shopUserId = $branch->printProvider->user_id;
        $adminId = User::role('admin')->value('id') ?? $customerId; // the payment is approved by an admin
        $last = in_array($status, ['rejected', 'cancelled'], true) ? 'processing' : $status;
        $steps = array_slice(self::STEPS, 0, array_search($last, self::STEPS, true) + 1);

        $previous = null;
        foreach ($steps as $index => $step) {
            if ($step !== 'awaiting_payment_review') {
                $this->step($order, $previous, $step, $step === 'processing' ? $adminId : $shopUserId, $at->copy()->addHours(3 * $index));
                OrderNotifier::statusChanged($customerId, $order->order_number, $step);
            }
            $previous = $step;
        }

        if ($status !== 'awaiting_payment_review') {
            OrderNotifier::newOrderForShop($shopUserId, $order->order_number, $order->items()->count(), $order->id);
        }

        foreach ($rejectedBy as $index => $rejecting) {
            $this->step($order, 'processing', 'rejected', $rejecting->printProvider->user_id, $at->copy()->addHours(4 + $index), $reason, ['rejected_branch_ids' => [$rejecting->id]]);
        }
        if ($rejectedBy) {
            OrderNotifier::rejectedByShop($order->order_number, (string) end($rejectedBy)->printProvider->company_name, (string) $reason);
            OrderNotifier::statusChanged($customerId, $order->order_number, 'rejected', $reason);
        }
        if ($status === 'cancelled') {
            $this->step($order, 'processing', 'cancelled', $customerId, $at->copy()->addHours(5), 'ألغى العميل الطلب قبل بدء الطباعة');
            OrderNotifier::statusChanged($customerId, $order->order_number, 'cancelled');
            OrderNotifier::cancelledForShop($shopUserId, $order->order_number);
        }
    }

    private function step(Order $order, ?string $from, string $to, int $by, Carbon $at, ?string $note = null, array $metadata = []): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id, 'changed_by' => $by, 'from_status' => $from, 'to_status' => $to,
            'note' => $note, 'metadata' => $metadata + ['source' => 'demo_seeder'],
        ])->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
    }

    private function shipment(Order $order, Address $address, string $status, Carbon $at): void
    {
        $partner = collect($this->delivery)->first(fn (DeliveryPartner $partner) => in_array($address->city, $partner->service_areas ?? [], true)) ?? $this->delivery[1];
        $number = substr($order->order_number, strlen(self::ORDER_PREFIX));
        $delivered = $status === 'delivered';

        Shipment::updateOrCreate(['shipment_number' => 'DEMO-SHIP-'.$number], [
            'order_id' => $order->id, 'delivery_partner_id' => $partner->id, 'tracking_number' => 'DEMO-TRK-'.$number,
            'status' => $delivered ? 'delivered' : 'in_transit', 'shipping_cost' => $order->shipping_cost, 'currency' => 'ILS',
            'delivery_address_snapshot' => $order->shipping_address_snapshot,
            'assigned_at' => $at->copy()->addHours(14), 'shipped_at' => $at->copy()->addHours(15),
            'estimated_delivery_at' => $at->copy()->addDays(2), 'delivered_at' => $delivered ? $at->copy()->addHours(18) : null,
        ]);
    }

    /** Each customer keeps a few published designs in their favourites. */
    private function favorites(): void
    {
        $published = collect($this->designs)->filter(fn (Design $design) => $design->status === 'published')->values();
        if ($published->isEmpty()) {
            return;
        }
        foreach ($this->customers as $number => [$user]) {
            foreach ([0, 2, 5] as $offset) {
                DesignFavorite::firstOrCreate(['user_id' => $user->id, 'design_id' => $published[($number + $offset) % $published->count()]->id]);
            }
        }
    }

    /**
     * Designer earnings from the demo orders: delivered ones are available to withdraw, the ones still on their way are
     * pending, and rejected or cancelled ones earn nothing.
     *
     * @param  array<int, User>  $designers
     */
    private function wallets(array $designers): void
    {
        foreach ($designers as $designer) {
            $wallet = Wallet::firstOrCreate(['user_id' => $designer->id], ['total_balance' => 0, 'available_balance' => 0, 'pending_balance' => 0, 'total_withdrawn' => 0]);

            $items = OrderItem::with('order')->where('designer_id', $designer->id)
                ->whereHas('order', fn ($query) => $query->where('order_number', 'like', self::ORDER_PREFIX.'%')
                    ->whereNotIn('status', ['awaiting_payment_review', 'rejected', 'cancelled']))
                ->get();

            $available = 0.0;
            $pending = 0.0;
            foreach ($items->groupBy('order_id') as $orderItems) {
                $order = $orderItems->first()->order;
                $amount = (float) $orderItems->sum('designer_profit');
                $done = $order->status === 'delivered';
                $done ? $available += $amount : $pending += $amount;

                WalletTransaction::updateOrCreate(['wallet_id' => $wallet->id, 'reference_id' => 'DEMO-WALLET-'.$order->order_number.'-'.$designer->id], [
                    'order_id' => $order->id, 'type' => 'earning', 'amount' => $amount, 'balance_after' => $available + $pending,
                    'description' => 'ربح تصميم من الطلب '.$order->order_number, 'status' => $done ? 'completed' : 'pending',
                ]);
            }

            $wallet->update(['total_balance' => $available + $pending, 'available_balance' => $available, 'pending_balance' => $pending, 'last_updated' => now()]);
            $designer->designerProfile?->update(['total_sales' => (int) $items->sum('quantity'), 'total_earnings' => $available + $pending]);
        }
    }
}
