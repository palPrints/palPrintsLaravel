<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesignController;
use App\Http\Controllers\Api\DesignerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PrintProviderController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
// test cmment
Route::pattern('id', '[0-9]+');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ✅ Test route - للتأكد من أن API شغال
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working! 🚀',
        'status' => 'success',
    ]);
});

// ========== Routes عامة (لا تحتاج توثيق) ==========
Route::prefix('auth')->group(function () {
    // تسجيل مستخدم جديد
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');

    // تسجيل الدخول
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    // طلب رابط إعادة تعيين كلمة المرور
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1');

    // إعادة تعيين كلمة المرور
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1');

    // تسجيل الخروج
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
});

// ✅ Protected routes (تحتاج توثيق)
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // جلب معلومات المستخدم الحالي
    Route::get('/user', [AuthController::class, 'me']);

    // ========== Routes خاصة بالمستخدمين ==========
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // ========== Routes خاصة بالتصاميم ==========
    Route::prefix('designs')->group(function () {
        Route::get('/', [DesignController::class, 'index']);
        Route::post('/', [DesignController::class, 'store']);
        Route::get('/{id}', [DesignController::class, 'show']);
        Route::put('/{id}', [DesignController::class, 'update']);
        Route::delete('/{id}', [DesignController::class, 'destroy']);
    });

    // ========== Routes خاصة بالطلبات ==========
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
    });

    // ========== Routes خاصة بالمنتجات ==========
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    // ========== Routes خاصة بالمطبوعات ==========
    Route::prefix('print-providers')->group(function () {
        Route::get('/', [PrintProviderController::class, 'index']);
        Route::get('/{id}', [PrintProviderController::class, 'show']);
        Route::put('/{id}', [PrintProviderController::class, 'update']);
        Route::post('/{id}/approve', [PrintProviderController::class, 'approve']);
    });

    // ========== Routes خاصة بالمصممين ==========
    Route::prefix('designers')->group(function () {
        Route::get('/', [DesignerController::class, 'index']);
        Route::get('/{id}', [DesignerController::class, 'show']);
        Route::put('/{id}', [DesignerController::class, 'update']);
        Route::post('/{id}/approve', [DesignerController::class, 'approve']);
    });
});
