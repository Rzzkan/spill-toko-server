<?php

use App\Http\Controllers\Admin\{AiUsageController, AppController, AuthController, CustomerController, AuditController, DashboardController, LayananController, OrderController, ProfileController, SettingsController};
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

        Route::get('/audit', [AuditController::class, 'index'])->name('audit');

        // Layanan Kelola Instagram & Threads
        Route::prefix('layanan')->name('layanan.')->group(function () {
            Route::redirect('/', '/admin/layanan/paket')->name('index');
            Route::get('/paket', [LayananController::class, 'plans'])->name('plans');
            Route::get('/jenis/baru', [LayananController::class, 'typeForm'])->name('type.create');
            Route::get('/jenis/{type}', [LayananController::class, 'typeForm'])->whereNumber('type')->name('type.edit');
            Route::post('/jenis', [LayananController::class, 'typeSave'])->name('type.store');
            Route::post('/jenis/{type}', [LayananController::class, 'typeSave'])->whereNumber('type')->name('type.update');
            Route::post('/jenis/{type}/hapus', [LayananController::class, 'typeDelete'])->whereNumber('type')->name('type.delete');
            Route::get('/paket/baru', [LayananController::class, 'planForm'])->name('plan.create');
            Route::get('/paket/{plan}', [LayananController::class, 'planForm'])->whereNumber('plan')->name('plan.edit');
            Route::post('/paket', [LayananController::class, 'planSave'])->name('plan.store');
            Route::post('/paket/{plan}', [LayananController::class, 'planSave'])->whereNumber('plan')->name('plan.update');
            Route::post('/paket/{plan}/hapus', [LayananController::class, 'planDelete'])->whereNumber('plan')->name('plan.delete');
            Route::get('/portofolio', [LayananController::class, 'portfolio'])->name('portfolio');
            Route::get('/portofolio/baru', [LayananController::class, 'portfolioForm'])->name('portfolio.create');
            Route::get('/portofolio/{item}', [LayananController::class, 'portfolioForm'])->whereNumber('item')->name('portfolio.edit');
            Route::post('/portofolio', [LayananController::class, 'portfolioSave'])->name('portfolio.store');
            Route::post('/portofolio/{item}', [LayananController::class, 'portfolioSave'])->whereNumber('item')->name('portfolio.update');
            Route::post('/portofolio/{item}/hapus', [LayananController::class, 'portfolioDelete'])->whereNumber('item')->name('portfolio.delete');
            Route::get('/langganan', [LayananController::class, 'subs'])->name('subs');
            Route::get('/langganan/baru', [LayananController::class, 'subForm'])->name('sub.create');
            Route::get('/langganan/{sub}', [LayananController::class, 'subForm'])->whereNumber('sub')->name('sub.edit');
            Route::post('/langganan', [LayananController::class, 'subSave'])->name('sub.store');
            Route::post('/langganan/{sub}', [LayananController::class, 'subSave'])->whereNumber('sub')->name('sub.update');
        });

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
