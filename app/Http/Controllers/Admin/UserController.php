<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\PrintProvider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AccountProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Design status => [label, users-page status pill class]. */
    private const DESIGN_STATES = [
        'draft' => ['مسودة', 'is-pending'],
        'review' => ['قيد المراجعة', 'is-pending'],
        'submitted' => ['قيد المراجعة', 'is-pending'],
        'published' => ['منشور', 'is-active'],
        'rejected' => ['مرفوض', 'is-suspended'],
    ];

    public function index(): View
    {
        return view('admin.users', [
            'usersData' => [
                'customers' => $this->customers(),
                'designers' => $this->designers(),
                'printShops' => $this->printShops(),
            ],
        ]);
    }

    /** Suspends an active account or activates a suspended one (customers, designers and print shops only). */
    public function toggle(Request $request, User $user): JsonResponse
    {
        abort_unless($user->hasAnyRole(['customer', 'designer', 'print_provider']) && ! $user->hasRole('admin'), 403);
        abort_if($user->is($request->user()), 403);

        $active = ! $user->is_active;
        $user->update(['is_active' => $active]);
        // A suspended shop must not receive new orders either.
        $user->printProvider?->update(['is_active' => $active]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $active ? 'admin.user_activated' : 'admin.user_suspended',
            'description' => ($active ? 'تفعيل' : 'تعطيل').' حساب المستخدم رقم '.$user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['user_id' => $user->id, 'is_active' => $active],
        ]);

        $profile = $user->hasRole('designer') ? $user->designerProfile : $user->printProvider;

        return response()->json([
            'status' => $this->onboardingStatus($user, $profile?->approval_status),
            'message' => $active ? 'تم تفعيل الحساب.' : 'تم تعطيل الحساب، ولن يستطيع المستخدم الدخول.',
        ]);
    }

    /** The admin adds a print shop directly: the account is created already verified and approved, so the shop can sign in at once. */
    public function storePrintShop(Request $request, AccountProvisioningService $accounts): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'password' => ['required', Password::defaults()],
        ], [
            'company_name.required' => 'اسم المطبعة مطلوب.',
            'name.required' => 'اسم المسؤول مطلوب.',
            'name.min' => 'يجب أن يتكون اسم المسؤول من 3 أحرف على الأقل.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم من قبل.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'address.required' => 'العنوان مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $admin = $request->user();

        $user = DB::transaction(function () use ($accounts, $validated, $admin): User {
            $user = $accounts->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'account_type' => 'print_provider',
                'email_verified_at' => now(),
            ]);
            $user->update(['phone' => $validated['phone']]);

            $provider = $user->printProvider;
            $provider->update([
                'company_name' => $validated['company_name'],
                'phone' => $validated['phone'],
                'contact_email' => $validated['email'],
                'address' => $validated['address'],
                'approval_status' => 'approved',
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'is_active' => true,
            ]);
            $provider->primaryBranch();

            Wallet::firstOrCreate(['user_id' => $user->id]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin.print_shop_created',
                'description' => 'إضافة مطبعة «'.$validated['company_name'].'» من الإدارة.',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'new_values' => ['user_id' => $user->id, 'company_name' => $validated['company_name']],
            ]);

            return $user;
        });

        return response()->json([
            'message' => 'تمت إضافة المطبعة، ويمكنها تسجيل الدخول الآن.',
            'user_id' => $user->id,
        ], 201);
    }

    private function customers(): array
    {
        $orders = Order::query()
            ->selectRaw('user_id, count(*) as orders_count, max(created_at) as last_order_at')
            ->selectRaw("sum(case when payment_status = 'paid' then total_amount else 0 end) as spent")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return User::role('customer')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (User $user) use ($orders) {
                $stats = $orders->get($user->id);

                return [
                    'userId' => $user->id,
                    'toggleUrl' => route('admin.users.toggle', $user),
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '—',
                    'city' => '—',
                    'registered' => $user->created_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'metric' => (int) ($stats?->orders_count ?? 0),
                    'spend' => '₪'.number_format((float) ($stats?->spent ?? 0)),
                    'lastOrder' => $stats?->last_order_at ? \Illuminate\Support\Carbon::parse($stats->last_order_at)->locale('ar')->translatedFormat('j F Y') : '—',
                    'status' => $user->is_active ? 'active' : 'suspended',
                ];
            })
            ->all();
    }

    private function designers(): array
    {
        if (! Schema::hasTable('designer_profiles')) {
            return [];
        }

        $users = User::role('designer')
            ->with(['designerProfile', 'approvalRequests' => fn ($query) => $query->latest('id')->limit(1)])
            ->orderByDesc('created_at')
            ->get();

        $designsByDesigner = Schema::hasTable('designs')
            ? Design::whereIn('designer_id', $users->pluck('id'))->latest()->get()->groupBy('designer_id')
            : collect();

        return $users
            ->map(function (User $user) use ($designsByDesigner) {
                /** @var DesignerProfile|null $profile */
                $profile = $user->designerProfile;
                $request = $user->approvalRequests->first();
                $designs = $designsByDesigner->get($user->id, collect());

                return [
                    'name' => $user->name,
                    'displayName' => $profile?->full_name ?: $user->name,
                    'avatar' => $this->storageUrl($profile?->profile_image),
                    'email' => $user->email,
                    'userId' => $user->id,
                    'toggleUrl' => route('admin.users.toggle', $user),
                    'phone' => $user->phone ?? '—',
                    'jobTitle' => $profile?->getAttribute('job_title') ?: '—',
                    'experience' => $profile?->getAttribute('experience') ?: '—',
                    'joined' => $user->created_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'portfolio' => $profile?->portfolio_url ?? '—',
                    'bio' => $profile?->bio ?? '—',
                    'skills' => $profile?->skills ? implode('، ', $profile->skills) : '—',
                    'metric' => $designs->count(),
                    'sales' => (int) ($profile?->total_sales ?? 0),
                    'revenue' => '₪'.number_format((float) ($profile?->total_earnings ?? 0)),
                    'status' => $this->onboardingStatus($user, $profile?->approval_status),
                    'submittedAt' => $request?->submitted_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'approvedAt' => $profile?->approved_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'adminNotes' => $request?->admin_notes,
                    'reviewUrl' => $this->reviewable($request) ? route('admin.approval-requests.review', $request->id) : null,
                    'designs' => $designs->map(fn (Design $design) => $this->designSummary($design))->values()->all(),
                ];
            })
            ->all();
    }

    private function designSummary(Design $design): array
    {
        [$label, $class] = self::DESIGN_STATES[$design->status] ?? [$design->status, 'is-pending'];

        return [
            'title' => $design->title,
            'image' => $design->image ? asset($design->image) : null,
            'date' => $design->created_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
            'statusLabel' => $label,
            'statusClass' => $class,
        ];
    }

    private function printShops(): array
    {
        if (! Schema::hasTable('print_providers')) {
            return [];
        }

        // The products each shop chose in its profile (its offerings), by provider.
        $productNames = \App\Models\BranchProductOffering::query()
            ->join('print_provider_branches', 'print_provider_branches.id', '=', 'branch_product_offerings.print_provider_branch_id')
            ->join('products', 'products.id', '=', 'branch_product_offerings.product_id')
            ->where(fn ($query) => $query->where('branch_product_offerings.is_active', true)->orWhere('branch_product_offerings.base_price', 0))
            ->select('print_provider_branches.print_provider_id', 'products.name')
            ->distinct()->orderBy('products.name')
            ->get()
            ->groupBy('print_provider_id')
            ->map(fn ($rows) => $rows->pluck('name')->map(fn ($name) => e($name))->implode('، '));

        // Real jobs per shop, from the order lines routed to its branches. The shop's earnings are not credited yet,
        // so the revenue shown is the shop's cost on its finished jobs.
        $jobs = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('print_provider_branches', 'print_provider_branches.id', '=', 'order_items.print_provider_branch_id')
            ->selectRaw('print_provider_branches.print_provider_id as provider_id')
            ->selectRaw("count(distinct case when orders.status = 'completed' then orders.id end) as done")
            ->selectRaw("count(distinct case when orders.status in ('processing', 'confirmed', 'ready') and orders.payment_status = 'paid' then orders.id end) as active")
            ->selectRaw("sum(case when orders.status = 'completed' then order_items.provider_cost else 0 end) as revenue")
            ->groupBy('print_provider_branches.print_provider_id')
            ->get()
            ->keyBy('provider_id');

        return User::role('print_provider')
            ->with(['printProvider', 'approvalRequests' => fn ($query) => $query->latest('id')->limit(1)])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (User $user) use ($productNames, $jobs) {
                /** @var PrintProvider|null $profile */
                $profile = $user->printProvider;
                $request = $user->approvalRequests->first();
                $stats = $profile ? $jobs->get($profile->id) : null;

                return [
                    'name' => $profile?->company_name ?? $user->name,
                    'userId' => $user->id,
                    'toggleUrl' => route('admin.users.toggle', $user),
                    'contactName' => $user->name,
                    'email' => $user->email,
                    'phone' => $profile?->phone ?? $user->phone ?? '—',
                    'whatsapp' => $profile?->whatsapp_number ?? '—',
                    'address' => $profile?->address ?? '—',
                    'products' => $profile ? ($productNames[$profile->id] ?? '') : '',
                    'city' => '—',
                    'joined' => $user->created_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'metric' => (int) ($stats?->done ?? 0),
                    'activeJobs' => (int) ($stats?->active ?? 0),
                    'jobsAvailable' => Schema::hasTable('orders'),
                    'delivery' => '—',
                    'revenue' => '₪'.number_format((float) ($stats?->revenue ?? 0)),
                    'workingHours' => $this->workingHoursSummary($profile?->working_hours),
                    'operating' => $profile?->is_active ?? true,
                    'contactEmail' => $profile?->getAttribute('contact_email') ?: '',
                    'status' => $this->onboardingStatus($user, $profile?->approval_status),
                    'submittedAt' => $request?->submitted_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'approvedAt' => $profile?->approved_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'adminNotes' => $request?->admin_notes,
                    'reviewUrl' => $this->reviewable($request) ? route('admin.approval-requests.review', $request->id) : null,
                ];
            })
            ->all();
    }

    private function storageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $path = ltrim($path, '/');

        return Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : null;
    }

    private function workingHoursSummary(?array $hours): string
    {
        if (! $hours || empty($hours['available'])) {
            return 'غير متاح حالياً';
        }

        $days = implode('، ', $hours['days'] ?? []);
        $from = $hours['from'] ?? null;
        $to = $hours['to'] ?? null;

        if (blank($days) && blank($from)) {
            return '—';
        }

        return trim($days.($from && $to ? ' ('.$from.' - '.$to.')' : ''));
    }

    private function onboardingStatus(User $user, ?string $approvalStatus): string
    {
        if (! $user->is_active) {
            return 'suspended';
        }

        // draft = has not sent a request yet; pending = sent, waiting for the admin; returned = rejected or sent back for changes.
        return match ($approvalStatus) {
            'approved' => 'active',
            'submitted', 'under_review' => 'pending',
            'rejected', 'changes_requested' => 'returned',
            default => 'draft',
        };
    }

    private function reviewable(?ApprovalRequest $request): bool
    {
        return $request !== null && in_array($request->status, ['submitted', 'under_review'], true);
    }
}
