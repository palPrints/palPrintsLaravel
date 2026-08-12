<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:customer,designer,print_provider'],
        ]);

        // تحديد حالة التفعيل حسب الدور
        $isActive = $request->role === 'customer' ? true : false;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $isActive,
            'is_verified' => false,
        ]);

        // إعطاء المستخدم الدور
        $user->assignRole($request->role);

        event(new Registered($user));

        // جميع المستخدمين يروحون لصفحة Login
        return redirect()->route('login')->with('status', 'تم إنشاء الحساب بنجاح! يرجى تسجيل الدخول.');
    }
}
