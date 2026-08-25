<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessAccountApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->supportsOnboarding() && ! $user->hasApprovedBusinessAccount()) {
            return redirect()
                ->route($user->dashboardRouteName())
                ->with('warning', 'هذه الميزة تصبح متاحة بعد اعتماد حسابك من الإدارة.');
        }

        return $next($request);
    }
}
