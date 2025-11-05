<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/password/reset-link', [PasswordResetController::class, 'sendResetLink']);
Route::post('/password/reset', [PasswordResetController::class, 'reset']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/user', [ProfileController::class, 'show']);
    Route::put('/user', [ProfileController::class, 'update']);
    Route::post('/user/password/change', [PasswordResetController::class, 'changePassword']);
    
    // Addresses
    Route::get('/user/addresses', [ProfileController::class, 'addresses']);
    Route::post('/user/addresses', [ProfileController::class, 'addAddress']);
    Route::put('/user/addresses/{id}', [ProfileController::class, 'updateAddress']);
    Route::delete('/user/addresses/{id}', [ProfileController::class, 'deleteAddress']);
    
    // 2FA
    Route::get('/2fa/setup', [TwoFactorController::class, 'setup']);
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable']);
    Route::get('/2fa/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes']);
    Route::post('/2fa/recovery-codes/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes']);
    
    // Logout
    Route::post('/logout', [LoginController::class, 'logout']);
});

// Public 2FA verification (for login flow)
Route::post('/2fa/verify', [TwoFactorController::class, 'verify']);

// Catalog Routes (Public)
Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
Route::get('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);
Route::get('/brands', [\App\Http\Controllers\Api\BrandController::class, 'index']);
Route::get('/brands/{id}', [\App\Http\Controllers\Api\BrandController::class, 'show']);
Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
Route::get('/products/{slug}', [\App\Http\Controllers\Api\ProductController::class, 'show']);

// Search Routes
Route::get('/search', [\App\Http\Controllers\Api\SearchController::class, 'search']);

// Admin Catalog Routes (Requires Auth + Admin Role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Categories
    Route::post('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'store']);
    Route::put('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'destroy']);
    
    // Brands
    Route::post('/brands', [\App\Http\Controllers\Api\BrandController::class, 'store']);
    Route::put('/brands/{id}', [\App\Http\Controllers\Api\BrandController::class, 'update']);
    Route::delete('/brands/{id}', [\App\Http\Controllers\Api\BrandController::class, 'destroy']);
    
    // Products
    Route::post('/products', [\App\Http\Controllers\Api\ProductController::class, 'store']);
    Route::put('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'update']);
    Route::delete('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'destroy']);
    
    // Product Variants
    Route::post('/products/{productId}/variants', [\App\Http\Controllers\Api\ProductVariantController::class, 'store']);
    Route::put('/products/{productId}/variants/{variantId}', [\App\Http\Controllers\Api\ProductVariantController::class, 'update']);
    Route::delete('/products/{productId}/variants/{variantId}', [\App\Http\Controllers\Api\ProductVariantController::class, 'destroy']);
});

// Cart Routes (Requires Auth or Session)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [\App\Http\Controllers\Api\CartController::class, 'show']);
    Route::post('/cart/items', [\App\Http\Controllers\Api\CartController::class, 'addItem']);
    Route::put('/cart/items/{itemId}', [\App\Http\Controllers\Api\CartController::class, 'updateItem']);
    Route::delete('/cart/items/{itemId}', [\App\Http\Controllers\Api\CartController::class, 'removeItem']);
    Route::delete('/cart', [\App\Http\Controllers\Api\CartController::class, 'clear']);
});

// Checkout & Orders (Requires Auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [\App\Http\Controllers\Api\CheckoutController::class, 'checkout']);
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
    
    // Payments
    Route::post('/orders/{orderId}/payments/initiate', [\App\Http\Controllers\Api\PaymentController::class, 'initiate']);
    Route::post('/payments/{paymentId}/verify', [\App\Http\Controllers\Api\PaymentController::class, 'verify']);
});

// Payment Callbacks (Public)
Route::get('/payments/zarinpal/callback', [\App\Http\Controllers\Api\PaymentController::class, 'zarinpalCallback']);

// Affiliate Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/affiliate/stats', [\App\Http\Controllers\Api\AffiliateController::class, 'stats']);
    Route::get('/affiliate/share/{productSlug}', [\App\Http\Controllers\Api\AffiliateController::class, 'generateShareLink']);
});

// Public affiliate tracking
Route::post('/affiliate/track', [\App\Http\Controllers\Api\AffiliateController::class, 'trackClick']);

// Lottery Routes
Route::get('/lotteries/product/{productSlug}', [\App\Http\Controllers\Api\LotteryController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders/{orderId}/lottery-entry', [\App\Http\Controllers\Api\LotteryController::class, 'createEntry']);
    Route::get('/lotteries/my-entries', [\App\Http\Controllers\Api\LotteryController::class, 'myEntries']);
    Route::get('/lotteries/{lotteryId}/stats', [\App\Http\Controllers\Api\LotteryController::class, 'stats']);
});

// Admin Lottery Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('/lotteries/{lotteryId}/draw', [\App\Http\Controllers\Api\LotteryController::class, 'draw']);
});

// Recommendations Routes
Route::get('/recommendations/personalized', [\App\Http\Controllers\Api\RecommendationController::class, 'personalized']);
Route::get('/recommendations/popular', [\App\Http\Controllers\Api\RecommendationController::class, 'popular']);
Route::get('/recommendations/related/{productId}', [\App\Http\Controllers\Api\RecommendationController::class, 'related']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/recommendations/purchase-history', [\App\Http\Controllers\Api\RecommendationController::class, 'purchaseHistory']);
});

// A/B Testing Routes
Route::get('/ab-test/{testKey}/variant', [\App\Http\Controllers\Api\ABTestController::class, 'getVariant']);
Route::post('/ab-test/{testKey}/track', [\App\Http\Controllers\Api\ABTestController::class, 'track']);

// Localization & Currency Routes
Route::get('/locale', [\App\Http\Controllers\Api\LocalizationController::class, 'getLocale']);
Route::post('/locale', [\App\Http\Controllers\Api\LocalizationController::class, 'setLocale']);
Route::get('/currencies', [\App\Http\Controllers\Api\CurrencyController::class, 'index']);
Route::get('/currencies/convert', [\App\Http\Controllers\Api\CurrencyController::class, 'convert']);
Route::get('/currencies/rate', [\App\Http\Controllers\Api\CurrencyController::class, 'rate']);

// Admin Dashboard Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'stats']);
    Route::get('/dashboard/sales-chart', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'salesChart']);
    Route::get('/dashboard/top-products', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'topProducts']);
    Route::get('/dashboard/users-chart', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'usersChart']);
    Route::get('/dashboard/category-stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'categoryStats']);
    Route::get('/dashboard/low-stock', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'lowStock']);
    Route::post('/dashboard/check-inventory-alerts', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'checkInventoryAlerts']);
    
    // User Management
    Route::get('/users', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'index']);
    Route::get('/users/{id}', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'show']);
    Route::put('/users/{id}/status', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'updateStatus']);
    Route::post('/users/{id}/roles', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'assignRole']);
    Route::delete('/users/{id}/roles/{roleId}', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'removeRole']);
    
    // Order Management
    Route::get('/orders', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'index']);
    Route::get('/orders/{id}', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'show']);
    Route::put('/orders/{id}/status', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'updateStatus']);
    
    // Reports
    Route::get('/reports/full', [\App\Http\Controllers\Api\Admin\ReportController::class, 'fullReport']);
    Route::get('/reports/sales', [\App\Http\Controllers\Api\Admin\ReportController::class, 'salesReport']);
    Route::get('/reports/products', [\App\Http\Controllers\Api\Admin\ReportController::class, 'productsReport']);
    Route::get('/reports/users', [\App\Http\Controllers\Api\Admin\ReportController::class, 'usersReport']);
    Route::get('/reports/payments', [\App\Http\Controllers\Api\Admin\ReportController::class, 'paymentsReport']);
});
