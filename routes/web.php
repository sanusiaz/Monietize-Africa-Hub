<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class);

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister']);
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::post('/webhooks/paystack', PaystackWebhookController::class);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/account', [StoreController::class, 'dashboard']);
    Route::get('/new-order', [StoreController::class, 'create']);
    Route::post('/new-order', [StoreController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/my-orders', [StoreController::class, 'orders']);
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/topup', [WalletController::class, 'topup'])->middleware('throttle:10,1');
    Route::get('/wallet/callback', [WalletController::class, 'callback'])->name('wallet.callback');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('users', [Admin\UserController::class, 'index']);
    Route::get('users/{user}', [Admin\UserController::class, 'show']);
    Route::put('users/{user}', [Admin\UserController::class, 'update']);
    Route::post('users/{user}/wallet', [Admin\UserController::class, 'adjustWallet']);

    Route::get('orders', [Admin\OrderController::class, 'index']);
    Route::put('orders/{order}', [Admin\OrderController::class, 'update']);
    Route::post('orders/{order}/submit', [Admin\OrderController::class, 'submit']);

    Route::get('providers/{provider}/balance', [Admin\ProviderController::class, 'balance']);
    Route::get('providers/{provider}/catalogue', [Admin\ProviderController::class, 'catalogue']);
    Route::post('providers/{provider}/import', [Admin\ProviderController::class, 'import']);

    Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('services', Admin\ServiceController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('providers', Admin\ProviderController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('payments', [Admin\PaymentController::class, 'index']);
    Route::post('payments/{payment}/verify', [Admin\PaymentController::class, 'verify']);

    Route::get('settings', [Admin\SettingController::class, 'edit']);
    Route::put('settings', [Admin\SettingController::class, 'update']);
    Route::post('settings/test-email', [Admin\SettingController::class, 'testEmail']);
    Route::get('emails/{type}', Admin\EmailPreviewController::class);

    Route::get('seo', [Admin\SeoController::class, 'edit']);
    Route::put('seo', [Admin\SeoController::class, 'update']);
    Route::post('seo/reset', [Admin\SeoController::class, 'reset']);

    Route::get('messages', [Admin\MessageController::class, 'index']);
    Route::post('messages/read-all', [Admin\MessageController::class, 'markAllRead']);
    Route::put('messages/{message}/read', [Admin\MessageController::class, 'toggleRead']);
    Route::delete('messages/{message}', [Admin\MessageController::class, 'destroy']);

    Route::post('pages/preview', [Admin\PageController::class, 'preview']);
    Route::resource('pages', Admin\PageController::class)->only(['index', 'store', 'update', 'destroy']);
});

Route::get('/sitemap.xml', [SitemapController::class, 'sitemap']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

Route::get('/contact', [ContactController::class, 'show']);
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1');

// admin-managed pages (Privacy Policy, Terms & Conditions, …) — keep this last so it never shadows a real route
Route::get('/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9-]+');
