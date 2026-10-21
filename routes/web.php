<?php

use App\Http\Controllers\Admin\ApprovalRequestController as AdminApprovalRequestController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DesignController as AdminDesignController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PaymentNoticeController as AdminPaymentNoticeController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\ShippingController as AdminShippingController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\StickerController as AdminStickerController;
use App\Http\Controllers\Customer\CartController as CustomerCartController;
use App\Http\Controllers\Customer\CatalogController as CustomerCatalogController;
use App\Http\Controllers\Customer\CheckoutController as CustomerCheckoutController;
use App\Http\Controllers\Customer\FavoriteController as CustomerFavoriteController;
use App\Http\Controllers\Customer\NotificationController as CustomerNotificationController;
use App\Http\Controllers\Customer\OrdersController as CustomerOrdersController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Customer\SettingsController as CustomerSettingsController;
use App\Http\Controllers\Customer\StickerBundleController as CustomerStickerBundleController;
use App\Http\Controllers\Customer\SupportController as CustomerSupportController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Designer\DashboardController as DesignerDashboardController;
use App\Http\Controllers\Designer\DesignController;
use App\Http\Controllers\Designer\EarningsController;
use App\Http\Controllers\Designer\NotificationController as DesignerNotificationController;
use App\Http\Controllers\PrintProvider\NotificationController as PrintProviderNotificationController;
use App\Http\Controllers\PrintProvider\OrdersController as PrintProviderOrdersController;
use App\Http\Controllers\Designer\ProfileController as DesignerProfileController;
use App\Http\Controllers\Designer\SettingsController as DesignerSettingsController;
use App\Http\Controllers\Designer\SupportController as DesignerSupportController;
use App\Http\Controllers\PrintProvider\DashboardController as PrintProviderDashboardController;
use App\Http\Controllers\PrintProvider\EarningsController as PrintProviderEarningsController;
use App\Http\Controllers\PrintProvider\ProfileController as PrintProviderProfileController;
use App\Http\Controllers\PrintProvider\ServicesController as PrintProviderServicesController;
use App\Http\Controllers\PrintProvider\SettingsController as PrintProviderSettingsController;
use App\Http\Controllers\PrintProvider\SupportController as PrintProviderSupportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PrintProvider\TelegramLinkController as PrintProviderTelegramLinkController;
use App\Http\Controllers\SharedOrderSheetController;
use App\Http\Controllers\SharedPrintFileController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\FontController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Home Page
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('index');
})->name('home');

// WhatsApp Cloud API webhook (Meta). Public, outside CSRF; the controller checks Meta's signature.
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->middleware('throttle:60,1')->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->middleware('throttle:600,1')->name('webhooks.whatsapp');
// Telegram bot webhook. Public, outside CSRF; the controller checks the secret token Telegram sends back.
Route::post('/webhooks/telegram', TelegramWebhookController::class)->middleware('throttle:600,1')->name('webhooks.telegram');

// The customer's print file, from the signed, expiring link in the WhatsApp message the admin sends the shop.
Route::get('/shared/print-files/{printFile}', SharedPrintFileController::class)
    ->middleware(['signed', 'throttle:30,1'])->name('print-files.shared');
// The details PDF of an order line, from the same WhatsApp message.
Route::get('/shared/order-items/{item}/details', SharedOrderSheetController::class)
    ->middleware(['signed', 'throttle:30,1'])->name('order-sheets.shared');

Route::view('/about', 'about')->name('about');
Route::view('/faq', 'pages.faq')->name('faq');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/returns', 'pages.returns')->name('returns');
Route::view('/shipping', 'pages.shipping')->name('shipping');

Route::view('/terms', 'pages.terms')->name('terms');

Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::get('/stickers/library/{stickerLibraryItem}/image', [CustomerStickerBundleController::class, 'libraryImage'])->name('stickers.library-image');
Route::get('/fonts/{customFont}/file', [FontController::class, 'file'])->name('fonts.file');
Route::middleware(['auth', 'active', 'role:customer|designer'])->prefix('api')->group(function () {
    Route::get('/fonts', [FontController::class, 'index'])->name('fonts.index');
    Route::get('/fonts/{customFont}', [FontController::class, 'show'])->name('fonts.show');
    // Uploading a font file is for designers; customers only use the fonts already there.
    Route::post('/fonts', [FontController::class, 'store'])->middleware('role:designer')->name('fonts.store');
});
Route::middleware(['auth', 'active', 'role:admin'])->prefix('api/admin')->group(function () {
    Route::patch('/fonts/{customFont}/status', [FontController::class, 'status'])->name('admin.fonts.status');
});

/*
|--------------------------------------------------------------------------
| Shop (open to visitors who are not signed in)
|--------------------------------------------------------------------------
| A visitor can browse and fill a cart; the cart lives on a hidden guest user
| (App\Support\GuestShopper) and moves to the customer's account at sign-in.
| Checkout, orders and the rest of the account stay behind login.
*/

// Design studio page, ported as-is from the frontend repo.
Route::get('/design-studio', function () {
    $isDesigner = auth()->user()?->hasRole('designer');

    // A designer reopening one of their own saved drafts (or rejected designs): the studio is filled from the saved design.
    $editDesign = null;
    if ($isDesigner && request()->filled('edit')) {
        $design = \App\Models\Design::withCount(['orderItems', 'cartItems'])
            ->where('designer_id', auth()->id())->find((int) request('edit'));
        abort_unless($design && \App\Http\Controllers\Designer\DesignController::abilities($design)['edit'], 404);
        $editDesign = \App\Http\Controllers\Designer\DesignController::studioData($design);
    }

    return view('studio.design-studio', [
        'editDesign' => $editDesign,
        'dbCatalog' => \App\Support\CatalogProductData::forDesigner()['products'],
        'chooseProductUrl' => $isDesigner ? route('designer.designs.create') : route('customer.chooseProduct'),
        // Both roles use the same preview page; it switches to the designer view for designers.
        'previewUrl' => $isDesigner ? route('designer.designs.preview') : route('customer.productPreview'),
        'workflowMode' => $isDesigner ? 'designer' : 'customer',
    ]);
})->middleware('shopper:customer,designer')->name('design-studio');

Route::middleware('shopper')->prefix('customer')->name('customer.')->group(function () {
    Route::get('/store', [CustomerCatalogController::class, 'store'])->name('store');
    Route::get('/choose-product', [CustomerCatalogController::class, 'chooseProduct'])->name('chooseProduct');
    Route::view('/product-preview', 'customer.productPreview')->name('productPreview');
    Route::get('/hoodies', [CustomerCatalogController::class, 'hoodies'])->name('hoodies');
    Route::get('/mugs', [CustomerCatalogController::class, 'mugs'])->name('mugs');
    Route::get('/tshirts', [CustomerCatalogController::class, 'tshirts'])->name('tshirts');
    Route::get('/stickers', [CustomerCatalogController::class, 'stickers'])->name('stickers');
    Route::post('/stickers/preview', [CustomerStickerBundleController::class, 'preview'])->name('stickers.preview');
    Route::get('/paper-printing', [CustomerCatalogController::class, 'paperPrinting'])->name('paperPrinting');
    Route::get('/basket', [CustomerCartController::class, 'index'])->name('basket');
    Route::post('/cart/quote-paper', [CustomerCartController::class, 'quotePaper'])->name('cart.quote-paper');
    Route::get('/print-files/{printFile}/preview', [CustomerCartController::class, 'printFilePreview'])->name('print-files.preview');
    Route::redirect('/basket/empty', '/customer/basket')->name('basket.empty');
    Route::get('/designs/{design}/assets/{assetId}', [CustomerCatalogController::class, 'designAsset'])->name('designs.asset');
    Route::patch('/cart/{cartItem}', [CustomerCartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cartItem}', [CustomerCartController::class, 'destroy'])->name('cart.destroy');
});

// Adding to the cart needs an account (a visitor is sent to sign in), so nothing can be uploaded anonymously.
Route::middleware(['auth', 'active', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::post('/cart/catalog', [CustomerCartController::class, 'storeCatalog'])->name('cart.store-catalog');
    Route::post('/cart/custom-design', [CustomerCartController::class, 'storeCustomDesign'])->name('cart.store-custom-design');
    Route::post('/cart/paper',[CustomerCartController::class, 'storePaper'])->name('cart.store-paper');
    Route::post('/cart/sticker-bundle', [CustomerStickerBundleController::class, 'store'])->name('cart.store-sticker-bundle');
    Route::post('/cart/sticker-bundle/{cartItem}', [CustomerStickerBundleController::class, 'update'])->name('cart.update-sticker-bundle');
});

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');



    Route::post('/onboarding/submit', [OnboardingController::class, 'submit'])
        ->name('onboarding.submit');

    // A print shop's identity and licence files: only the admin and the shop's owner (checked in the controller).
    Route::get('/print-provider-documents/{provider}/{field}', \App\Http\Controllers\PrintProvider\DocumentController::class)
        ->whereNumber('provider')
        ->whereIn('field', \App\Http\Controllers\PrintProvider\DocumentController::FIELDS)
        ->name('print-provider.documents');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/designs', [AdminDesignController::class, 'index'])->name('designs');
        Route::post('/designs/{design}/review', [AdminDesignController::class, 'review'])->name('designs.review');
        Route::get('/designs/{design}/files/{assetId}', [AdminDesignController::class, 'file'])->name('designs.file');

        Route::get('/products', [AdminProductController::class, 'index'])->name('products');
        Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
        Route::patch('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}/toggle', [AdminProductController::class, 'toggle'])->name('products.toggle');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/stickers', [AdminStickerController::class, 'index'])->name('stickers');
        Route::post('/stickers/categories', [AdminStickerController::class, 'storeCategory'])->name('stickers.categories.store');
        Route::patch('/stickers/categories/{stickerCategory}', [AdminStickerController::class, 'updateCategory'])->name('stickers.categories.update');
        Route::delete('/stickers/categories/{stickerCategory}', [AdminStickerController::class, 'destroyCategory'])->name('stickers.categories.destroy');
        Route::post('/stickers/items', [AdminStickerController::class, 'storeItem'])->name('stickers.items.store');
        Route::patch('/stickers/items/{stickerLibraryItem}', [AdminStickerController::class, 'updateItem'])->name('stickers.items.update');
        Route::delete('/stickers/items/{stickerLibraryItem}', [AdminStickerController::class, 'destroyItem'])->name('stickers.items.destroy');
        Route::post('/stickers/settings', [AdminStickerController::class, 'updateSettings'])->name('stickers.settings.update');
        Route::post('/stickers/preview', [AdminStickerController::class, 'preview'])->name('stickers.preview');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
        Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
        Route::post('/orders/{order}/reroute', [AdminOrderController::class, 'reroute'])->whereNumber('order')->name('orders.reroute');
        Route::get('/orders/{order}/payment-receipt', [AdminOrderController::class, 'paymentReceipt'])->name('orders.payment-receipt');

        Route::get('/payment-notices', [AdminPaymentNoticeController::class, 'index'])->name('payment-notices');
        Route::post('/payment-notices/{payment}/approve', [AdminPaymentNoticeController::class, 'approve'])->name('payment-notices.approve');
        Route::post('/payment-notices/{payment}/reject', [AdminPaymentNoticeController::class, 'reject'])->name('payment-notices.reject');
        Route::get('/payment-notices/{payment}/receipt', [AdminPaymentNoticeController::class, 'receipt'])->name('payment-notices.receipt');

        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments');
        Route::post('/payments/withdrawals/{withdrawal}/review', [AdminPaymentController::class, 'review'])->name('payments.withdrawals.review');

        Route::get('/shipping', [AdminShippingController::class, 'index'])->name('shipping');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings');
        Route::post('/settings/general', [AdminSettingController::class, 'updateGeneral'])->name('settings.general');
        Route::post('/settings/notifications', [AdminSettingController::class, 'updateNotifications'])->name('settings.notifications');
        Route::post('/settings/fees', [AdminSettingController::class, 'updateFees'])->name('settings.fees');
        Route::post('/settings/pages/{page}', [AdminSettingController::class, 'updatePage'])->name('settings.pages');
        Route::post('/settings/site', [AdminSettingController::class, 'updateSite'])->name('settings.site');
        Route::post('/settings/payments/{method}', [AdminSettingController::class, 'updatePaymentMethod'])->name('settings.payments');
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [AdminNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [AdminNotificationController::class, 'read'])->name('notifications.read');
        Route::get('/support', [AdminSupportController::class, 'index'])->name('support');
        Route::get('/support/{ticket}', [AdminSupportController::class, 'show'])->name('support.show');
        Route::get('/support/{ticket}/messages', [AdminSupportController::class, 'messages'])->name('support.messages');
        Route::post('/support/{ticket}/reply', [AdminSupportController::class, 'reply'])->name('support.reply');
        Route::post('/support/{ticket}/status', [AdminSupportController::class, 'updateStatus'])->name('support.status');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggle'])->name('users.toggle');
        Route::get('/profile', [AdminProfileController::class, 'show'])->name('profile');
        Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/approval-requests/{approvalRequest}/review', [AdminApprovalRequestController::class, 'update'])->name('approval-requests.review');
    });
    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function () {
        Route::get('/checkout', [CustomerCheckoutController::class, 'show'])->name('checkout');
        Route::post('/checkout', [CustomerCheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/orders', [CustomerOrdersController::class, 'index'])->name('orders');
        Route::post('/orders/{order}/cancel', [CustomerOrdersController::class, 'cancel'])->name('orders.cancel');
        Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('profile');
        Route::patch('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');
        Route::get('/favorites', [CustomerFavoriteController::class, 'index'])->name('favorites');
        Route::post('/designs/{design}/favorite', [CustomerFavoriteController::class, 'toggle'])->name('designs.favorite');
        Route::get('/settings', [CustomerSettingsController::class, 'edit'])->name('settings');
        Route::get('/support', [CustomerSupportController::class, 'edit'])->name('support');
        Route::post('/support', [CustomerSupportController::class, 'store'])->name('support.store');
        Route::get('/support/tickets/{ticket}', [CustomerSupportController::class, 'showTicket'])->name('support.show');
        Route::get('/support/tickets/{ticket}/messages', [CustomerSupportController::class, 'ticketMessages'])->name('support.messages');
        Route::post('/support/tickets/{ticket}/reply', [CustomerSupportController::class, 'replyTicket'])->name('support.reply');
        Route::get('/notifications', [CustomerNotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [CustomerNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [CustomerNotificationController::class, 'read'])->name('notifications.read');
    });
    Route::get('/designer/dashboard', DesignerDashboardController::class)
        ->middleware('role:designer')->name('designer.dashboard');
    Route::get('/print-provider/dashboard', PrintProviderDashboardController::class)
        ->middleware('role:print_provider')->name('print-provider.dashboard');
    Route::get('/print-provider/profile', [PrintProviderProfileController::class, 'show'])
        ->middleware('role:print_provider')->name('print-provider.profile');
    Route::patch('/print-provider/profile', [PrintProviderProfileController::class, 'update'])
        ->middleware('role:print_provider')->name('print-provider.profile.update');
    Route::post('/print-provider/telegram/link', [PrintProviderTelegramLinkController::class, 'link'])
        ->middleware('role:print_provider')->name('print-provider.telegram.link');
    Route::delete('/print-provider/telegram/link', [PrintProviderTelegramLinkController::class, 'unlink'])
        ->middleware('role:print_provider')->name('print-provider.telegram.unlink');
    Route::middleware(['role:print_provider', 'account.approved'])->prefix('print-provider/requests')->name('print-provider.requests')->group(function () {
        Route::get('/', [PrintProviderOrdersController::class, 'index']);
        Route::post('/{order}/accept', [PrintProviderOrdersController::class, 'accept'])->name('.accept');
        Route::post('/{order}/reject', [PrintProviderOrdersController::class, 'reject'])->name('.reject');
        Route::post('/{order}/ready', [PrintProviderOrdersController::class, 'ready'])->name('.ready');
        Route::get('/{order}/files/{printFile}', [PrintProviderOrdersController::class, 'downloadFile'])->name('.files');
        Route::get('/{order}/items/{item}/package', [PrintProviderOrdersController::class, 'package'])->name('.package');
        Route::get('/{order}/items/{item}/sheet', [PrintProviderOrdersController::class, 'sheet'])->name('.sheet');
    });
    Route::get('/print-provider/earnings', PrintProviderEarningsController::class)
        ->middleware(['role:print_provider', 'account.approved'])->name('print-provider.earnings');
    Route::post('/print-provider/earnings/withdraw', [PrintProviderEarningsController::class, 'withdraw'])
        ->middleware(['role:print_provider', 'account.approved'])->name('print-provider.earnings.withdraw');
    Route::middleware(['role:print_provider', 'account.approved'])->prefix('print-provider/services')->name('print-provider.services')->group(function () {
        Route::get('/', [PrintProviderServicesController::class, 'index']);
        Route::put('/products/{product}', [PrintProviderServicesController::class, 'save'])->name('.save');
        Route::patch('/products/{product}/toggle', [PrintProviderServicesController::class, 'toggle'])->name('.toggle');
    });
    Route::middleware('role:print_provider')->prefix('print-provider')->name('print-provider.')->group(function () {
        Route::get('/settings', [PrintProviderSettingsController::class, 'show'])->name('settings');
        Route::patch('/settings/account', [PrintProviderSettingsController::class, 'updateAccount'])->name('settings.account');
        Route::get('/support', [PrintProviderSupportController::class, 'show'])->name('support');
        Route::post('/support', [PrintProviderSupportController::class, 'store'])->name('support.store');
        Route::get('/support/tickets/{ticket}', [PrintProviderSupportController::class, 'showTicket'])->name('support.show');
        Route::get('/support/tickets/{ticket}/messages', [PrintProviderSupportController::class, 'ticketMessages'])->name('support.messages');
        Route::post('/support/tickets/{ticket}/reply', [PrintProviderSupportController::class, 'replyTicket'])->name('support.reply');
        Route::get('/notifications', [PrintProviderNotificationController::class, 'index'])->name('notifications');
        Route::get('/notifications/feed', [PrintProviderNotificationController::class, 'feed'])->middleware('throttle:30,1')->name('notifications.feed');
        Route::post('/notifications/read-all', [PrintProviderNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [PrintProviderNotificationController::class, 'read'])->name('notifications.read');
    });
    Route::middleware('role:designer')->prefix('designer')->name('designer.')->group(function () {
        Route::middleware('account.approved')->group(function () {
            Route::get('/designs', [DesignController::class, 'index'])->name('designs.index');
            Route::get('/designs/create', [DesignController::class, 'create'])->name('designs.create');
            Route::view('/designs/preview', 'customer.productPreview')->name('designs.preview');
            Route::get('/designs/review', [DesignController::class, 'review'])->name('designs.review');
            Route::get('/designs/{design}', [DesignController::class, 'show'])->whereNumber('design')->name('designs.show');
            Route::post('/designs', [DesignController::class, 'store'])->name('designs.store');
            Route::get('/designs/{design}/files/{assetId}', [DesignController::class, 'file'])->whereNumber('design')->name('designs.file');
            Route::delete('/designs/{design}', [DesignController::class, 'destroy'])->whereNumber('design')->name('designs.destroy');
            Route::post('/designs/{design}/withdraw', [DesignController::class, 'withdraw'])->whereNumber('design')->name('designs.withdraw');
            Route::post('/designs/{design}/unpublish', [DesignController::class, 'unpublish'])->whereNumber('design')->name('designs.unpublish');
            Route::post('/designs/{design}/duplicate', [DesignController::class, 'duplicate'])->whereNumber('design')->name('designs.duplicate');
        });
        Route::get('/profile', [DesignerProfileController::class, 'show'])->name('profile');
        Route::patch('/profile', [DesignerProfileController::class, 'update'])->name('profile.update');
        Route::middleware('account.approved')->group(function () {
            Route::get('/earnings', EarningsController::class)->name('earnings');
            Route::post('/earnings/withdraw', [EarningsController::class, 'withdraw'])->name('earnings.withdraw');
        });
        Route::get('/settings', [DesignerSettingsController::class, 'show'])->name('settings');
        Route::patch('/settings/account', [DesignerSettingsController::class, 'updateAccount'])->name('settings.account');
        Route::get('/support', [DesignerSupportController::class, 'show'])->name('support');
        Route::post('/support', [DesignerSupportController::class, 'store'])->name('support.store');
        Route::get('/support/tickets/{ticket}', [DesignerSupportController::class, 'showTicket'])->name('support.show');
        Route::get('/support/tickets/{ticket}/messages', [DesignerSupportController::class, 'ticketMessages'])->name('support.messages');
        Route::post('/support/tickets/{ticket}/reply', [DesignerSupportController::class, 'replyTicket'])->name('support.reply');
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
