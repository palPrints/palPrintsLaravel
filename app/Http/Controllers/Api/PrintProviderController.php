<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrintProviderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->hasPermission($request->user(), 'view users')) {
            return $this->error('ليس لديك صلاحية لعرض قائمة المطابع.', 403);
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'approval_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $providers = User::query()
            ->where('role', 'print_provider')
            ->with(['printProvider', 'roles:id,name'])
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('printProvider', function ($query) use ($search) {
                            $query->where('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($validated['approval_status'] ?? null, function ($query, string $status) {
                if ($status === 'pending') {
                    $query->where(function ($query) {
                        $query->whereDoesntHave('printProvider')
                            ->orWhereHas('printProvider', fn ($query) => $query->where('approval_status', 'pending'));
                    });

                    return;
                }

                $query->whereHas(
                    'printProvider',
                    fn ($query) => $query->where('approval_status', $status)
                );
            })
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->success($providers, 'تم جلب المطابع بنجاح.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $provider = $this->findProvider($id);
        $currentUser = $request->user();

        if (! $this->isAdmin($currentUser) && ! $currentUser->is($provider)) {
            return $this->error('ليس لديك صلاحية لعرض بيانات هذه المطبعة.', 403);
        }

        return $this->success(
            $provider->load(['printProvider', 'roles:id,name']),
            'تم جلب بيانات المطبعة بنجاح.'
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $provider = $this->findProvider($id);
        $currentUser = $request->user();

        if (! $this->isAdmin($currentUser) && ! $currentUser->is($provider)) {
            return $this->error('ليس لديك صلاحية لتعديل بيانات هذه المطبعة.', 403);
        }

        $profile = $provider->printProvider;

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'user_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'company_name' => [Rule::requiredIf($profile === null), 'string', 'max:255'],
            'address' => [Rule::requiredIf($profile === null), 'string', 'max:2000'],
            'phone' => [Rule::requiredIf($profile === null), 'string', 'max:30'],
            'whatsapp_number' => [Rule::requiredIf($profile === null), 'string', 'max:30'],
            'working_hours' => ['sometimes', 'nullable', 'string', 'max:255'],
            'license_document' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'verification_document' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $userData = [];

        if (array_key_exists('name', $validated)) {
            $userData['name'] = $validated['name'];
        }

        if (array_key_exists('user_phone', $validated)) {
            $userData['phone'] = $validated['user_phone'];
        }

        $profileData = array_intersect_key($validated, array_flip([
            'company_name',
            'address',
            'phone',
            'whatsapp_number',
            'working_hours',
            'license_document',
            'verification_document',
            'is_active',
        ]));

        DB::transaction(function () use ($provider, $userData, $profileData): void {
            if ($userData !== []) {
                $provider->update($userData);
            }

            if ($profileData !== []) {
                $provider->printProvider()->updateOrCreate(
                    ['user_id' => $provider->id],
                    $profileData
                );
            }
        });

        return $this->success(
            $provider->fresh()->load(['printProvider', 'roles:id,name']),
            'تم تعديل بيانات المطبعة بنجاح.'
        );
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        if (! $this->hasPermission($request->user(), 'approve print_providers')) {
            return $this->error('ليس لديك صلاحية للموافقة على المطابع.', 403);
        }

        $provider = $this->findProvider($id);
        $profile = $provider->printProvider;

        if (! $profile) {
            return $this->error('يجب استكمال ملف المطبعة قبل الموافقة على الحساب.', 422);
        }

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['approved', 'rejected'])],
            'rejection_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
        ]);

        $status = $validated['status'] ?? 'approved';

        DB::transaction(function () use ($provider, $profile, $validated, $status): void {
            $profile->update([
                'approval_status' => $status,
                'approved_at' => $status === 'approved' ? now() : null,
                'rejection_reason' => $status === 'rejected'
                    ? $validated['rejection_reason']
                    : null,
                'is_active' => $status === 'approved',
            ]);

            $provider->update([
                'is_active' => $status === 'approved',
                'is_verified' => $status === 'approved',
            ]);

            if ($status === 'rejected') {
                $provider->tokens()->delete();
            }
        });

        $message = $status === 'approved'
            ? 'تمت الموافقة على المطبعة بنجاح.'
            : 'تم رفض المطبعة وتعطيل حسابها.';

        return $this->success(
            $provider->fresh()->load(['printProvider', 'roles:id,name']),
            $message
        );
    }

    private function findProvider(int $id): User
    {
        return User::where('role', 'print_provider')->findOrFail($id);
    }
}
