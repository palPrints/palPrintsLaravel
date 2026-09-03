<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $hasActiveFlag = $user && array_key_exists('is_active', $user->getAttributes());

        if (! $user || ! $hasActiveFlag || $user->is_active) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $locale = in_array($request->session()->get('auth_locale'), ['ar', 'en'], true)
            ? $request->session()->get('auth_locale')
            : 'ar';
        $translations = [
            'ar' => trans('auth.login.session_inactive', [], 'ar'),
            'en' => trans('auth.login.session_inactive', [], 'en'),
        ];

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        app()->setLocale($locale);

        return redirect()->route('login')
            ->with('error', $translations[$locale])
            ->with('login_error_translations', ['general' => $translations]);
    }
}
