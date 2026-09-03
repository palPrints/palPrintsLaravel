<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->supportsOnboarding() && ! $user->hasCompletedRoleProfile()) {
            return redirect()
                ->route($user->dashboardRouteName())
                ->with('warning', 'أكمل بيانات ملف نشاطك أولًا لاستخدام هذه الميزة.');
        }

        return $next($request);
    }
}
