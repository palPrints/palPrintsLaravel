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

        if (! $user || $user->is_active) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            $currentAccessToken = $user->currentAccessToken();

            if ($currentAccessToken && method_exists($currentAccessToken, 'delete')) {
                $currentAccessToken->delete();
            }

            return response()->json([
                'status' => 'error',
                'message' => 'حسابك غير مفعّل أو ما زال قيد مراجعة الإدارة.',
                'data' => [
                    'account_status' => 'pending',
                ],
            ], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('error', 'حسابك قيد المراجعة من قبل الإدارة.');
    }
}
