<?php

namespace App\Providers;

use App\Models\Design;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
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
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });

        View::composer('admin.partials.sidebar', function ($view): void {
            $view->with('adminPendingDesigns', Design::whereIn('status', ['review', 'submitted'])->count());
        });

        View::composer('admin.partials.topbar', function ($view): void {
            $user = Auth::user();

            $view->with([
                'adminUnreadCount' => $user?->userNotifications()->where('is_read', false)->count() ?? 0,
                'adminNotifications' => $user
                    ? $user->userNotifications()->latest()->limit(3)->get()
                    : collect(),
            ]);
        });

        View::composer('designer.partials.topbar', function ($view): void {
            $user = Auth::user();

            $view->with([
                'designerUnreadCount' => $user?->userNotifications()->where('is_read', false)->count() ?? 0,
                'designerLatestNotifications' => $user
                    ? $user->userNotifications()->latest()->limit(3)->get()
                    : collect(),
            ]);
        });
    }
}
