<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->hasPermission($request->user(), 'view users')) {
            return $this->error('ليس لديك صلاحية لعرض المستخدمين.', 403);
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in($this->roles())],
            'is_active' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->with('roles:id,name')
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when(array_key_exists('is_active', $validated), fn ($query) => $query->where('is_active', $validated['is_active']))
            ->when(array_key_exists('is_verified', $validated), fn ($query) => $query->where('is_verified', $validated['is_verified']))
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->success($users, 'تم جلب المستخدمين بنجاح.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $this->hasPermission($request->user(), 'view users')) {
            return $this->error('ليس لديك صلاحية لعرض بيانات المستخدم.', 403);
        }

        $user = User::with([
            'roles:id,name',
            'designerProfile',
            'printProvider',
            'deliveryPartner',
        ])->findOrFail($id);

        return $this->success($user, 'تم جلب بيانات المستخدم بنجاح.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();

        if (! $this->hasPermission($currentUser, 'edit users')) {
            return $this->error('ليس لديك صلاحية لتعديل المستخدمين.', 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'role' => ['sometimes', 'required', Rule::in($this->roles())],
            'is_active' => ['sometimes', 'boolean'],
            'is_verified' => ['sometimes', 'boolean'],
        ]);

        if ($currentUser->is($user)) {
            if (isset($validated['role']) && $validated['role'] !== 'admin') {
                return $this->error('لا يمكنك إزالة دور الإدارة عن حسابك بنفسك.', 422);
            }

            if (array_key_exists('is_active', $validated) && ! $validated['is_active']) {
                return $this->error('لا يمكنك تعطيل حسابك الإداري بنفسك.', 422);
            }
        }

        if (isset($validated['email'])) {
            $validated['email'] = Str::lower(trim($validated['email']));
        }

        DB::transaction(function () use ($user, $validated): void {
            $user->update($validated);

            if (isset($validated['role'])) {
                $user->syncRoles([$validated['role']]);
            }
        });

        return $this->success(
            $user->fresh()->load('roles:id,name'),
            'تم تعديل المستخدم بنجاح.'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();

        if (! $this->hasPermission($currentUser, 'delete users')) {
            return $this->error('ليس لديك صلاحية لحذف المستخدمين.', 403);
        }

        $user = User::findOrFail($id);

        if ($currentUser->is($user)) {
            return $this->error('لا يمكنك حذف حسابك الإداري الحالي.', 422);
        }

        if ($user->orders()->exists() || $user->designs()->exists()) {
            return $this->error(
                'لا يمكن حذف المستخدم لوجود طلبات أو تصاميم مرتبطة به. يمكنك تعطيل الحساب بدلًا من حذفه.',
                409
            );
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });

        return $this->success(null, 'تم حذف المستخدم بنجاح.');
    }

    /**
     * @return list<string>
     */
    private function roles(): array
    {
        return ['customer', 'designer', 'print_provider', 'admin'];
    }
}
