<?php

namespace App\Providers;

use App\Models\ApprovalRequest;
use App\Models\Design;
use App\Models\Order;
use App\Models\WithdrawalRequest;
use App\Support\SupportTicketStore;
use App\Support\AdminNotifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Event::listen(Registered::class, function (Registered $event): void {
            $user = $event->user;

            AdminNotifier::toAdmins(
                'user.registered',
                'مستخدم جديد',
                sprintf('انضم %s (%s) إلى المنصة.', $user->name, $user->email),
                route('admin.users'),
            );
        });

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });

        View::composer('admin.partials.sidebar', function ($view): void {
            $unread = fn (string $category) => Auth::user()?->userNotifications()->where('is_read', false)->where(AdminNotifier::scope($category))->count() ?? 0;

            $view->with([
                'adminPendingDesigns' => Design::whereIn('status', ['review', 'submitted'])->count(),
                'adminSidebarBadges' => [
                    'admin.orders' => $unread('orders'),
                    'admin.payment-notices' => Order::where('payment_status', 'pending_review')->count(),
                    'admin.payments' => WithdrawalRequest::where('status', 'pending')->count(),
                    'designers' => ApprovalRequest::where('role', 'designer')->where('status', 'submitted')->count(),
                    'printShops' => ApprovalRequest::where('role', 'print_provider')->where('status', 'submitted')->count(),
                    'admin.support' => SupportTicketStore::all()->where('status', 'new')->count(),
                ],
            ]);
        });

        View::composer('admin.partials.topbar', function ($view): void {
            $user = Auth::user();

            $view->with([
                'adminUnreadCount' => $user?->userNotifications()->where('is_read', false)->count() ?? 0,
                'adminNotifications' => $user
                    ? $user->userNotifications()->where('is_read', false)->latest()->limit(3)->get()
                    : collect(),
            ]);
        });

        View::composer('designer.partials.topbar', function ($view): void {
            $user = Auth::user();

            $view->with([
                'designerUnreadCount' => $user?->userNotifications()->where('is_read', false)->count() ?? 0,
                'designerLatestNotifications' => $user
                    ? $user->userNotifications()->where('is_read', false)->latest()->limit(3)->get()
                    : collect(),
            ]);
        });

        View::composer('printProvider.partials.topbar', function ($view): void {
            $user = Auth::user();

            $view->with([
                'providerUnreadCount' => $user?->userNotifications()->where('is_read', false)->count() ?? 0,
                'providerLatestNotifications' => $user
                    ? $user->userNotifications()->where('is_read', false)->latest()->limit(5)->get()
                    : collect(),
            ]);
        });

        View::composer('customer.partials.header', function ($view): void {
            $user = Auth::user();

            $cartItems = $user
                ? \App\Models\CartItem::query()
                    ->whereHas('cart', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))
                    ->with('product')
                    ->latest()
                    ->get()
                : collect();

            $view->with([
                'customerUnreadCount' => $user?->userNotifications()->where('is_read', false)->count() ?? 0,
                'customerLatestNotifications' => $user
                    ? $user->userNotifications()->where('is_read', false)->latest()->limit(5)->get()
                    : collect(),
                'customerCartItems' => $cartItems,
                'customerCartCount' => $cartItems->sum('quantity'),
            ]);
        });

        // Role-aware link helper for the public info pages (footer, FAQ, contact...), same one the home page defines inline.
        View::composer('pages.*', function ($view): void {
            $viewer = Auth::user();

            $view->with('toRole', fn (string $role, string $route) => $viewer && ! $viewer->hasRole($role) ? route('dashboard') : route($route));
        });

        // Links used by resources/views/errors/*. Wrapped so a broken session/route never turns an error page into a second error.
        View::composer(['errors.*', 'errors::*'], function ($view): void {
            $homeUrl = url('/');
            try {
                $user = Auth::user();
                $storeUrl = $user && ! $user->hasRole('customer') ? route('dashboard') : route('customer.store');
                $supportUrl = $user && $user->hasRole('customer') ? route('customer.support') : route('login');
                $loginUrl = route('login');
            } catch (\Throwable) {
                $storeUrl = $supportUrl = $loginUrl = $homeUrl;
            }

            $view->with(compact('homeUrl', 'storeUrl', 'supportUrl', 'loginUrl'));
        });
    }
}
