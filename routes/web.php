<?php

use App\Http\Controllers\Admin\ApprovalRequestController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\OnboardingController;
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
    Route::get('/designer/dashboard', [RoleDashboardController::class, 'show'])
        ->defaults('dashboard_role', 'designer')->middleware('role:designer')->name('designer.dashboard');
    Route::get('/print-provider/dashboard', [RoleDashboardController::class, 'show'])
        ->defaults('dashboard_role', 'print_provider')->middleware('role:print_provider')->name('print-provider.dashboard');
    Route::get('/delivery-partner/dashboard', [RoleDashboardController::class, 'show'])
        ->defaults('dashboard_role', 'delivery_partner')->middleware('role:delivery_partner')->name('delivery-partner.dashboard');

    Route::post('/onboarding/submit', [OnboardingController::class, 'submit'])
        ->middleware('role:designer|print_provider|delivery_partner')
        ->name('onboarding.submit');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::patch('/approval-requests/{approvalRequest}', [ApprovalRequestController::class, 'update'])
            ->name('approval-requests.update');
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
