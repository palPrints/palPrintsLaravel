<?php

use App\Http\Controllers\Customer\CatalogController as CustomerCatalogController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Designer\DashboardController as DesignerDashboardController;
use App\Http\Controllers\Designer\DesignController;
use App\Http\Controllers\Designer\EarningsController;
use App\Http\Controllers\Designer\NotificationController as DesignerNotificationController;
use App\Http\Controllers\Designer\ProfileController as DesignerProfileController;
use App\Http\Controllers\Designer\SettingsController as DesignerSettingsController;
use App\Http\Controllers\PrintProvider\EarningsController as PrintProviderEarningsController;
use App\Http\Controllers\PrintProvider\ProfileController as PrintProviderProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Home Page
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('index');
})->name('home');

Route::view('/terms', 'legal.placeholder', [
    'title' => 'الشروط والأحكام',
    'message' => 'هذه الصفحة مهيأة لإضافة النص القانوني النهائي قبل إطلاق المنصة.',
])->name('terms');

Route::view('/privacy', 'legal.placeholder', [
    'title' => 'سياسة الخصوصية',
    'message' => 'هذه الصفحة مهيأة لإضافة سياسة الخصوصية النهائية قبل إطلاق المنصة.',
])->name('privacy');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::post('/onboarding/submit', [OnboardingController::class, 'submit'])
        ->name('onboarding.submit');

    Route::get('/admin/dashboard', [RoleDashboardController::class, 'show'])
        ->defaults('dashboard_role', 'admin')->middleware('role:admin')->name('admin.dashboard');
    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function () {
        Route::get('/store', [CustomerCatalogController::class, 'store'])->name('store');
        Route::get('/hoodies', [CustomerCatalogController::class, 'hoodies'])->name('hoodies');
        Route::get('/mugs', [CustomerCatalogController::class, 'mugs'])->name('mugs');
        Route::get('/tshirts', [CustomerCatalogController::class, 'tshirts'])->name('tshirts');
        Route::view('/stickers', 'customer.stickers')->name('stickers');
        Route::view('/paper-printing', 'customer.paperPrinting')->name('paperPrinting');
        Route::view('/product-preview', 'customer.productPreview')->name('productPreview');
        Route::view('/basket', 'customer.basket')->name('basket');
        Route::view('/basket/empty', 'customer.basket-empty')->name('basket.empty');
        Route::view('/checkout', 'customer.checkout')->name('checkout');
        Route::view('/orders', 'customer.orders')->name('orders');
        Route::view('/profile', 'customer.profile')->name('profile');
        Route::view('/favorites', 'customer.favorites')->name('favorites');
    });
    Route::get('/designer/dashboard', DesignerDashboardController::class)
        ->middleware('role:designer')->name('designer.dashboard');
    Route::view('/print-provider/dashboard', 'printProvider.dashboard')
        ->middleware('role:print_provider')->name('print-provider.dashboard');
    Route::get('/print-provider/profile', [PrintProviderProfileController::class, 'show'])
        ->middleware('role:print_provider')->name('print-provider.profile');
    Route::patch('/print-provider/profile', [PrintProviderProfileController::class, 'update'])
        ->middleware('role:print_provider')->name('print-provider.profile.update');
    Route::view('/print-provider/requests', 'printProvider.requests')
        ->middleware('role:print_provider')->name('print-provider.requests');
    Route::get('/print-provider/earnings', PrintProviderEarningsController::class)
        ->middleware('role:print_provider')->name('print-provider.earnings');
    Route::post('/print-provider/earnings/withdraw', [PrintProviderEarningsController::class, 'withdraw'])
        ->middleware('role:print_provider')->name('print-provider.earnings.withdraw');
    Route::view('/print-provider/services', 'printProvider.services')
        ->middleware('role:print_provider')->name('print-provider.services');
    Route::middleware('role:designer')->prefix('designer')->name('designer.')->group(function () {
        Route::middleware('account.approved')->group(function () {
            Route::get('/designs', [DesignController::class, 'index'])->name('designs.index');
            Route::get('/designs/create', [DesignController::class, 'create'])->name('designs.create');
            Route::get('/designs/editor', [DesignController::class, 'editor'])->name('designs.editor');
            Route::get('/designs/review', [DesignController::class, 'review'])->name('designs.review');
            Route::post('/designs', [DesignController::class, 'store'])->name('designs.store');
        });
        Route::get('/profile', [DesignerProfileController::class, 'show'])->name('profile');
        Route::patch('/profile', [DesignerProfileController::class, 'update'])->name('profile.update');
        Route::middleware('account.approved')->group(function () {
            Route::get('/earnings', EarningsController::class)->name('earnings');
            Route::post('/earnings/withdraw', [EarningsController::class, 'withdraw'])->name('earnings.withdraw');
        });
        Route::get('/settings', [DesignerSettingsController::class, 'show'])->name('settings');
        Route::patch('/settings/account', [DesignerSettingsController::class, 'updateAccount'])->name('settings.account');
        Route::view('/support', 'designer.support')->name('support');
        Route::get('/notifications', [DesignerNotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [DesignerNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [DesignerNotificationController::class, 'read'])->name('notifications.read');
        Route::view('/trends', 'designer.trends')->name('trends');
    });

});

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
