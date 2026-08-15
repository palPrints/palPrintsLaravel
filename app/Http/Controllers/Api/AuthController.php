<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    /**
     * تسجيل مستخدم جديد.
     */
    public function register(Request $request): JsonResponse
    {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'role' => ['required', 'in:customer,designer,print_provider'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_active' => $validated['role'] === 'customer',
                'is_verified' => false,
            ]);

            $user->assignRole($validated['role']);

            return $user;
        });

        event(new Registered($user));

        $message = $user->is_active
            ? 'تم إنشاء الحساب بنجاح. يمكنك تسجيل الدخول الآن.'
            : 'تم إنشاء الحساب بنجاح، والحساب قيد مراجعة الإدارة.';

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'user' => $this->userData($user),
                'account_status' => $user->is_active ? 'active' : 'pending',
            ],
        ], 201);
    }

    /**
     * تسجيل الدخول وإصدار Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'حسابك قيد المراجعة من قبل الإدارة.',
                'data' => [
                    'account_status' => 'pending',
                ],
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الدخول بنجاح.',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->userData($user),
            ],
        ]);
    }

    /**
     * جلب بيانات المستخدم المسجل دخوله.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'تم جلب بيانات المستخدم بنجاح.',
            'data' => [
                'user' => $this->userData($request->user()),
            ],
        ]);
    }

    /**
     * حذف الـtoken المستخدم في الطلب الحالي فقط.
     */
    public function logout(Request $request): JsonResponse
    {
        $currentAccessToken = $request->user()->currentAccessToken();

        if ($currentAccessToken && method_exists($currentAccessToken, 'delete')) {
            $currentAccessToken->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الخروج بنجاح.',
        ]);
    }

    /**
     * إرسال رابط إعادة تعيين كلمة المرور.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'status' => 'error',
                'message' => 'تم إرسال طلب مؤخرًا. يرجى الانتظار قبل المحاولة مرة أخرى.',
            ], 429);
        }

        // رسالة عامة حتى لا نكشف إن كان البريد مسجلاً في النظام أم لا.
        return response()->json([
            'status' => 'success',
            'message' => 'إذا كان البريد مسجلاً، فسيتم إرسال رابط إعادة تعيين كلمة المرور.',
        ]);
    }

    /**
     * حفظ كلمة المرور الجديدة باستخدام الرمز المرسل إلى البريد.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // إلغاء كل جلسات API القديمة بعد تغيير كلمة المرور.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'error',
                'message' => 'تعذر إعادة تعيين كلمة المرور. تحقق من البريد والرمز ثم حاول مجددًا.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.',
        ]);
    }

    /**
     * الحقول الآمنة التي يحتاجها Front-End فقط.
     *
     * @return array<string, mixed>
     */
    private function userData(User $user): array
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
        ];
    }

    private function normalizeEmail(Request $request): void
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge([
                'email' => Str::lower(trim($email)),
            ]);
        }
    }
}
