<?php

use App\Http\Controllers\Web\PortalController;
use App\Http\Middleware\PortalAuth;
use App\Services\Plans;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('public.home', [
    'trial' => Plans::trialDays(), 'plans' => Plans::all(), 'aiq' => \App\Services\Ai::quotas(), 'aiOn' => \App\Services\Ai::on(), 'store' => Settings::get('app.chrome_store_url', ''),
]));
Route::get('/thanks', fn (Request $r) => view('public.thanks', ['order' => preg_replace('/[^A-Za-z0-9\-]/', '', (string) $r->query('order'))]));

// Portal pengguna: masuk dengan Google, status langganan, perpanjang
Route::get('/login', [PortalController::class, 'loginForm'])->name('portal.login');
Route::redirect('/masuk', '/login', 301);   // tautan lama
Route::post('/login/google', [PortalController::class, 'loginGoogle'])->middleware('throttle:15,1')->name('portal.login.google');
Route::get('/beli/{plan}', [PortalController::class, 'buy'])->whereAlpha('plan')->name('portal.buy');
Route::get('/beli/{app}/{plan}', [PortalController::class, 'buy'])->where(['app' => '[a-z][a-z0-9\-]{1,29}', 'plan' => '[a-z]+'])->name('portal.buy.app');
Route::middleware(PortalAuth::class)->group(function () {
    Route::get('/beli-lanjut', [PortalController::class, 'buyGo'])->middleware('throttle:10,1')->name('portal.buy.go');
    Route::get('/akun', [PortalController::class, 'account'])->name('portal.account');
    Route::get('/akun/aplikasi/{app}', [PortalController::class, 'app'])->where('app', '[a-z][a-z0-9\-]{1,29}')->name('portal.app');
    Route::post('/akun/aplikasi/{app}/bayar', [PortalController::class, 'pay'])->where('app', '[a-z][a-z0-9\-]{1,29}')->middleware('throttle:10,1')->name('portal.pay');
    Route::post('/akun/bayar', [PortalController::class, 'pay'])->middleware('throttle:10,1')->name('portal.pay.legacy');   // tautan lama = Shofee
    Route::delete('/akun/browser/{id}', [PortalController::class, 'revokeDevice'])->whereNumber('id')->middleware('throttle:20,1')->name('portal.device.revoke');
    Route::post('/keluar', [PortalController::class, 'logout'])->name('portal.logout');
});

require __DIR__ . '/admin.php';
