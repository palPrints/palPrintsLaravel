<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\PrintProvider;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

    private function customers(): array
    {
        return User::role('customer')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (User $user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '—',
                    'city' => '—',
                    'registered' => $user->created_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'metric' => 0,
                    'spend' => '₪0',
                    'lastOrder' => '—',
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
                    'phone' => $user->phone ?? '—',
                    'city' => '—',
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

        return User::role('print_provider')
            ->with(['printProvider', 'approvalRequests' => fn ($query) => $query->latest('id')->limit(1)])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (User $user) {
                /** @var PrintProvider|null $profile */
                $profile = $user->printProvider;
                $request = $user->approvalRequests->first();

                return [
                    'name' => $profile?->company_name ?? $user->name,
                    'contactName' => $user->name,
                    'email' => $user->email,
                    'phone' => $profile?->phone ?? $user->phone ?? '—',
                    'whatsapp' => $profile?->whatsapp_number ?? '—',
                    'address' => $profile?->address ?? '—',
                    'city' => '—',
                    'joined' => $user->created_at?->locale('ar')->translatedFormat('j F Y') ?? '—',
                    'metric' => (int) ($profile?->total_orders ?? 0),
                    'activeJobs' => 0,
                    'jobsAvailable' => Schema::hasTable('orders'),
                    'delivery' => '—',
                    'revenue' => '₪'.number_format((float) ($profile?->total_earnings ?? 0)),
                    'workingHours' => $this->workingHoursSummary($profile?->working_hours),
                    'operating' => $profile?->is_active ?? true,
                    'licenseDocument' => $this->storageUrl($profile?->license_document),
                    'verificationDocument' => $this->storageUrl($profile?->verification_document),
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

        return $approvalStatus === 'approved' ? 'active' : 'pending';
    }

    private function reviewable(?ApprovalRequest $request): bool
    {
        return $request !== null && in_array($request->status, ['submitted', 'under_review'], true);
    }
}
