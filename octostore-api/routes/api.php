<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Apply tenant resolution to all routes so Login can detect context
Route::middleware(['tenant'])->group(function () {

    // Public Catalog Routes
    Route::prefix('v1')->group(function () {
        Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
        Route::get('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);

        Route::get('/brands', [\App\Http\Controllers\Api\BrandController::class, 'index']);

        Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
        Route::get('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'show']);

        // Cart Routes
        Route::get('/cart', [\App\Http\Controllers\Api\CartController::class, 'index']);
        Route::post('/cart/items', [\App\Http\Controllers\Api\CartController::class, 'addItem']);
        Route::put('/cart/items/{id}', [\App\Http\Controllers\Api\CartController::class, 'updateItem']);
        Route::delete('/cart/items/{id}', [\App\Http\Controllers\Api\CartController::class, 'removeItem']);
        Route::post('/cart/checkout/draft', [\App\Http\Controllers\Api\CartController::class, 'checkoutDraft']);
        Route::post('/cart/coupon', [\App\Http\Controllers\Api\CartController::class, 'applyCoupon']);
        Route::get('/cart/shipping-methods', [\App\Http\Controllers\Api\CartController::class, 'estimateShipping']);

        // Promotions
        Route::get('/banners', [\App\Http\Controllers\Api\PromotionController::class, 'getBanners']);
        Route::get('/flash-deals', [\App\Http\Controllers\Api\PromotionController::class, 'getFlashDeals']);
        Route::get('/flash-deals/{slug}', [\App\Http\Controllers\Api\PromotionController::class, 'getFlashDealProducts']);

        // Delivery Checks
        Route::post('/delivery/check', [\App\Http\Controllers\Api\DeliveryController::class, 'checkZone']);
        Route::get('/delivery/slots', [\App\Http\Controllers\Api\DeliveryController::class, 'getTimeSlots']);

        // Payment Routes
        // Note: callback is strictly public usually for webhooks, but here we place it depending on architecture.
        // Webhooks should be outside of "tenant" middleware if provider doesn't support dynamic URLs well, 
        // but here assuming tenant context is preserved or passed in URL.

        // Public Engagement Listings
        Route::get('/products/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'index']);
        Route::get('/products/{id}/questions', [\App\Http\Controllers\Api\QuestionController::class, 'index']);

        // AI Features
        Route::post('/ai/search-assist', [\App\Http\Controllers\Api\AiController::class, 'searchAssist']);
        Route::post('/ai/review-summary', [\App\Http\Controllers\Api\AiController::class, 'reviewSummary']);
        Route::post('/ai/recommendations', [\App\Http\Controllers\Api\AiController::class, 'recommendations']);

        // Analytics
        Route::post('/analytics/track', [\App\Http\Controllers\Api\AnalyticsController::class, 'track']);
    });

    // Public Payment Webhooks / Pages (Outside Auth, Tenant middleware context kept)
    Route::middleware(['tenant'])->group(function () {
        Route::post('/orders/{id}/pay', [\App\Http\Controllers\Api\PaymentController::class, 'initiate']);
        // Supports both GET and POST for callbacks depending on provider
        Route::match(['get', 'post'], '/payment/callback/{gateway}', [\App\Http\Controllers\Api\PaymentController::class, 'callback'])->name('payment.callback');
        Route::get('/payment/mock-page', [\App\Http\Controllers\Api\PaymentController::class, 'mockPage'])->name('payment.mock.page');
    });

    // ...

    // Authenticated Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Admin Catalog Routes
        Route::prefix('v1/admin')->group(function () {
            // ... existing routes ...
            Route::get('/delivery/zones', [\App\Http\Controllers\Api\Admin\AdminDeliveryController::class, 'indexZones']);
            Route::post('/delivery/zones', [\App\Http\Controllers\Api\Admin\AdminDeliveryController::class, 'storeZone']);
            Route::put('/delivery/zones/{zone}', [\App\Http\Controllers\Api\Admin\AdminDeliveryController::class, 'updateZone']);
            Route::delete('/delivery/zones/{zone}', [\App\Http\Controllers\Api\Admin\AdminDeliveryController::class, 'deleteZone']);

            Route::get('/delivery/slots', [\App\Http\Controllers\Api\Admin\AdminDeliveryController::class, 'indexSlots']);
            Route::post('/delivery/slots', [\App\Http\Controllers\Api\Admin\AdminDeliveryController::class, 'storeSlot']);
            Route::delete('/delivery/slots/{slot}', [\App\Http\Controllers\Api\Admin\AdminDeliveryController::class, 'deleteSlot']);

            // Notification Templates
            Route::apiResource('/notifications/templates', \App\Http\Controllers\Api\Admin\AdminNotificationController::class);

            // Content Moderation
            Route::get('/reviews', [\App\Http\Controllers\Api\Admin\AdminReviewController::class, 'index']);
            Route::put('/reviews/{review}/status', [\App\Http\Controllers\Api\Admin\AdminReviewController::class, 'updateStatus']);

            // Dashboard
            Route::get('/dashboard/stats', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'stats']);

            Route::post('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'store']);
            Route::put('/categories/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'destroy']);

            Route::post('/brands', [\App\Http\Controllers\Api\BrandController::class, 'store']);

            Route::post('/products', [\App\Http\Controllers\Api\ProductController::class, 'store']);

            // Admin Order Routes
            Route::get('/orders', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'index']);
            Route::get('/orders/export', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'exportCsv']);
            Route::get('/orders/{id}', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'show']);
            Route::put('/orders/{order}/status', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'updateStatus']);
            // Add update/destroy for products/brands as needed
        });

        // Super Admin Routes (Secure via IP, separate auth, or dedicated guard)
        // Using prefix /v1/super-admin
        Route::prefix('v1/super-admin')->group(function () {
            // Tenants
            Route::get('/tenants', [\App\Http\Controllers\Api\SuperAdmin\TenantManagementController::class, 'index']);
            Route::post('/tenants', [\App\Http\Controllers\Api\SuperAdmin\TenantManagementController::class, 'store']);
            Route::post('/tenants/{tenant}/maintenance', [\App\Http\Controllers\Api\SuperAdmin\TenantManagementController::class, 'maintenance']);

            // Plans
            // Route::resource('/plans', ...);
        });

        // Customer Protected Routes
        Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']);

        // Engagement
        Route::post('/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
        Route::post('/questions', [\App\Http\Controllers\Api\QuestionController::class, 'store']);

        // Tenant Routes Protected
        Route::get('/tenant-data', function () {
            return response()->json([
                'data' => 'Secret Tenant Data',
                'tenant' => app()->bound('tenant') ? app('tenant')->name : 'Master'
            ]);
        });

        Route::middleware(['role:Manager|Owner'])->get('/high-level-report', function () {
            return "Report";
        });
    });
});
