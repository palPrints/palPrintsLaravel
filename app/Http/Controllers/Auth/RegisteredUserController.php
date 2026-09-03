<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\AuditLog;
use App\Services\AccountProvisioningService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(
        RegisterRequest $request,
        AccountProvisioningService $accounts
    ): RedirectResponse {
        $validated = $request->validated();

        $user = $accounts->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'account_type' => $validated['account_type'],
            'locale' => app()->getLocale(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'account.registered',
            'description' => 'تم إنشاء حساب جديد بدور '.$validated['account_type'].'.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['role' => $validated['account_type']],
        ]);

        event(new Registered($user));

        return redirect()
            ->route('login')
            ->with('status', 'تم إنشاء حسابك بنجاح. يمكنك تسجيل الدخول الآن.');
    }
}
