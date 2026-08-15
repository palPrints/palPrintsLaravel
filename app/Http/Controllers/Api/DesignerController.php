<?php

namespace App\Http\Controllers\Api;

use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DesignerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->hasPermission($request->user(), 'view users')) {
            return $this->error('ليس لديك صلاحية لعرض قائمة المصممين.', 403);
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'approval_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $designers = User::query()
            ->where('role', 'designer')
            ->with(['designerProfile', 'roles:id,name'])
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('designerProfile', function ($query) use ($search) {
                            $query->where('full_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($validated['approval_status'] ?? null, function ($query, string $status) {
                if ($status === 'pending') {
                    $query->where(function ($query) {
                        $query->whereDoesntHave('designerProfile')
                            ->orWhereHas('designerProfile', fn ($query) => $query->where('approval_status', 'pending'));
                    });

                    return;
                }

                $query->whereHas(
                    'designerProfile',
                    fn ($query) => $query->where('approval_status', $status)
                );
            })
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->success($designers, 'تم جلب المصممين بنجاح.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $designer = $this->findDesigner($id);
        $currentUser = $request->user();

        if (! $this->isAdmin($currentUser) && ! $currentUser->is($designer)) {
            return $this->error('ليس لديك صلاحية لعرض بيانات هذا المصمم.', 403);
        }

        return $this->success(
            $designer->load(['designerProfile', 'roles:id,name']),
            'تم جلب بيانات المصمم بنجاح.'
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $designer = $this->findDesigner($id);
        $currentUser = $request->user();

        if (! $this->isAdmin($currentUser) && ! $currentUser->is($designer)) {
            return $this->error('ليس لديك صلاحية لتعديل بيانات هذا المصمم.', 403);
        }

        $profile = $designer->designerProfile;

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'full_name' => [Rule::requiredIf($profile === null), 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'skills' => ['sometimes', 'nullable', 'array', 'max:30'],
            'skills.*' => ['string', 'max:100'],
            'portfolio_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'profile_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ]);

        $userData = array_intersect_key($validated, array_flip(['name', 'phone']));
        $profileData = array_intersect_key($validated, array_flip([
            'full_name',
            'bio',
            'skills',
            'portfolio_url',
            'profile_image',
        ]));

        DB::transaction(function () use ($designer, $userData, $profileData): void {
            if ($userData !== []) {
                $designer->update($userData);
            }

            if ($profileData !== []) {
                $designer->designerProfile()->updateOrCreate(
                    ['user_id' => $designer->id],
                    $profileData
                );
            }
        });

        return $this->success(
            $designer->fresh()->load(['designerProfile', 'roles:id,name']),
            'تم تعديل بيانات المصمم بنجاح.'
        );
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        if (! $this->hasPermission($request->user(), 'approve designers')) {
            return $this->error('ليس لديك صلاحية للموافقة على المصممين.', 403);
        }

        $designer = $this->findDesigner($id);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['approved', 'rejected'])],
            'rejection_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
        ]);

        $status = $validated['status'] ?? 'approved';

        DB::transaction(function () use ($designer, $validated, $status): void {
            $profile = $designer->designerProfile()->firstOrCreate(
                ['user_id' => $designer->id],
                ['full_name' => $designer->name]
            );

            $profile->update([
                'approval_status' => $status,
                'approved_at' => $status === 'approved' ? now() : null,
                'rejection_reason' => $status === 'rejected'
                    ? $validated['rejection_reason']
                    : null,
            ]);

            $designer->update([
                'is_active' => $status === 'approved',
                'is_verified' => $status === 'approved',
            ]);

            if ($status === 'rejected') {
                $designer->tokens()->delete();
            }
        });

        $message = $status === 'approved'
            ? 'تمت الموافقة على المصمم بنجاح.'
            : 'تم رفض المصمم وتعطيل حسابه.';

        return $this->success(
            $designer->fresh()->load(['designerProfile', 'roles:id,name']),
            $message
        );
    }

    private function findDesigner(int $id): User
    {
        return User::where('role', 'designer')->findOrFail($id);
    }
}
