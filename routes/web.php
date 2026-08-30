<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Designer\DashboardController as DesignerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleDashboardController;
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

    Route::get('/admin/dashboard', [RoleDashboardController::class, 'show'])
        ->defaults('dashboard_role', 'admin')->middleware('role:admin')->name('admin.dashboard');
    Route::get('/customer/dashboard', [RoleDashboardController::class, 'show'])
        ->defaults('dashboard_role', 'customer')->middleware('role:customer')->name('customer.dashboard');
    Route::get('/designer/dashboard', DesignerDashboardController::class)
        ->middleware('role:designer')->name('designer.dashboard');
    Route::get('/print-provider/dashboard', [RoleDashboardController::class, 'show'])
        ->defaults('dashboard_role', 'print_provider')->middleware('role:print_provider')->name('print-provider.dashboard');
    Route::middleware('role:designer')->prefix('designer')->name('designer.')->group(function () {
        Route::view('/designs', 'designer.designs.index')->name('designs.index');
        Route::view('/designs/create', 'designer.designs.create')->name('designs.create');
        Route::view('/profile', 'designer.profile')->name('profile');
        Route::view('/earnings', 'designer.earnings')->name('earnings');
        Route::view('/settings', 'designer.settings')->name('settings');
        Route::view('/support', 'designer.support')->name('support');
        Route::view('/notifications', 'designer.notifications')->name('notifications');
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
