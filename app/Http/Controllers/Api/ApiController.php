<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(
        mixed $data = null,
        string $message = 'تمت العملية بنجاح.',
        int $status = 200
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    protected function error(string $message, int $status, mixed $data = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    protected function isAdmin(User $user): bool
    {
        return $user->role === 'admin' || $user->hasRole('admin');
    }

    protected function hasPermission(User $user, string $permission): bool
    {
        return $this->isAdmin($user) || $user->can($permission);
    }

    /**
     * @return array<string, mixed>
     */
    protected function userSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'is_verified' => $user->is_verified,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
