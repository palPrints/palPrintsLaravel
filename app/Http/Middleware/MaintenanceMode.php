<?php

namespace App\Http\Middleware;

use App\Support\PlatformSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settings > General > "وضع الصيانة": the public site shows the 503 page while it is on.
 * Signed-in admins keep working, and the sign-in pages stay open so an admin can still get in.
 */
class MaintenanceMode
{
    /** Paths that stay reachable during maintenance. */
    private const OPEN_PATHS = ['login', 'logout', 'forgot-password', 'reset-password', 'reset-password/*', 'auth/*', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! PlatformSettings::get('general', 'maintenance_mode', false)
            || $request->user()?->hasRole('admin')
            || $request->is(...self::OPEN_PATHS)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'الموقع غير متاح حاليًا بسبب صيانة قصيرة. عُد بعد قليل.'], 503);
        }

        return response()->view('errors.503', [], 503)->header('Retry-After', '3600');
    }
}
