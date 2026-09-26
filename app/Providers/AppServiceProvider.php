<?php

namespace App\Providers;

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
