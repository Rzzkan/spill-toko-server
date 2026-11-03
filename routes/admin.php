<?php

use App\Http\Controllers\Admin\{AiUsageController, AppController, AuthController, CustomerController, DashboardController, OrderController, ProfileController, SettingsController};
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'form'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/pengguna', [CustomerController::class, 'index'])->name('customers');
        Route::get('/pengguna/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('/pengguna/{customer}', [CustomerController::class, 'update'])->name('customers.update');

        Route::get('/aplikasi', [AppController::class, 'index'])->name('apps');
        Route::get('/aplikasi/baru', [AppController::class, 'create'])->name('apps.create');
        Route::post('/aplikasi', [AppController::class, 'store'])->name('apps.store');
        Route::get('/aplikasi/{app:slug}', [AppController::class, 'edit'])->name('apps.edit');
        Route::post('/aplikasi/{app:slug}', [AppController::class, 'update'])->name('apps.update');

        Route::get('/order', [OrderController::class, 'index'])->name('orders');

        Route::get('/pemakaian-ai', [AiUsageController::class, 'index'])->name('ai');
        Route::post('/pemakaian-ai/kosongkan-cache', [AiUsageController::class, 'clearCache'])->name('ai.clear');

        Route::get('/pengaturan', fn () => redirect()->route('admin.settings', 'google'))->name('settings.index');
        Route::get('/pengaturan/{group}', [SettingsController::class, 'show'])->name('settings');
        Route::post('/pengaturan/{group}', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/pengaturan-tes/{what}', [SettingsController::class, 'test'])->name('settings.test');
        Route::post('/pengaturan-kunci-lisensi', [SettingsController::class, 'regenerateKey'])->name('settings.regen');

        Route::get('/profil', [ProfileController::class, 'show'])->name('profile');
        Route::post('/profil', [ProfileController::class, 'update'])->name('profile.update');
    });
});
