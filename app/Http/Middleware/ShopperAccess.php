<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\GuestShopper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shop pages and the cart are open to visitors who are not signed in.
 *
 * Usage: `shopper` (browse; a guest cart is only used if one already exists) or `shopper:create`
 * (cart additions: a guest cart is created on the spot). Further arguments list the roles a signed-in user may
 * have; the default is customer. A visitor is lent a guest user for the request only, never logged in.
 */
class ShopperAccess
{
    public function handle(Request $request, Closure $next, string ...$options): Response
    {
        $create = in_array('create', $options, true);
        $roles = array_values(array_diff($options, ['create'])) ?: ['customer'];

        $user = Auth::user();

        if ($user) {
            // Same rules the signed-in customer routes always had: active account, allowed role.
            if (array_key_exists('is_active', $user->getAttributes()) && ! $user->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }
            abort_unless($user->hasAnyRole($roles), 403);

            return $next($request);
        }

        $guest = GuestShopper::fromSession($request) ?? ($create ? GuestShopper::create($request) : null);

        if ($guest) {
            $this->keepAlive($guest);
            Auth::setUser($guest);
            $request->setUserResolver(fn () => $guest);
        }

        return $next($request);
    }

    /** The clean-up job removes guests untouched for days; an active visitor must not be removed. */
    private function keepAlive(User $guest): void
    {
        if ($guest->updated_at && $guest->updated_at->lt(now()->subHour())) {
            $guest->forceFill(['updated_at' => now()])->saveQuietly();
        }
    }
}
