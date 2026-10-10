<?php

namespace App\Http\Middleware;

use App\Support\GuestShopper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Right after a visitor signs in (or registers) as a customer, the cart they filled as a guest becomes theirs.
 * Runs on every web request because sign-in can happen in several places (form, Google, Apple, registration).
 */
class MergeGuestCart
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->session()->has(GuestShopper::SESSION_KEY)) {
            $user = $request->user();

            if ($user && ! $user->isGuestShopper() && $user->hasRole('customer')) {
                $guest = GuestShopper::fromSession($request);
                $request->session()->forget(GuestShopper::SESSION_KEY);

                if ($guest) {
                    GuestShopper::merge($guest, $user);
                }
            }
        }

        return $next($request);
    }
}
