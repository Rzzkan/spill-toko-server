<?php

use App\Http\Controllers\Web\PortalController;
use App\Http\Middleware\PortalAuth;
use App\Services\Plans;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Web\PublicController::class, 'home'])->name('home');
Route::get('/shofee', [\App\Http\Controllers\Web\PublicController::class, 'shofee'])->name('shofee');
Route::get('/layanan', [\App\Http\Controllers\Web\LayananController::class, 'index'])->name('layanan');
Route::get('/layanan/pesan/{plan}', [\App\Http\Controllers\Web\LayananController::class, 'start'])->whereNumber('plan')->name('layanan.start');
Route::get('/layanan/gambar/{id}/{v}', [\App\Http\Controllers\Web\LayananController::class, 'image'])->whereNumber('id')->name('layanan.image');
Route::get('/privasi', [\App\Http\Controllers\Web\PublicController::class, 'privacy'])->name('privacy');
Route::redirect('/privacy', '/privasi', 301);
Route::get('/thanks', [\App\Http\Controllers\Web\PublicController::class, 'thanks'])->middleware('throttle:30,1')->name('thanks');

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
    Route::get('/akun/layanan', [\App\Http\Controllers\Web\LayananController::class, 'account'])->name('portal.layanan');
    Route::post('/akun/layanan/pesan', [\App\Http\Controllers\Web\LayananController::class, 'order'])->middleware('throttle:6,1')->name('portal.layanan.order');
    Route::post('/akun/layanan/{sub}/catatan', [\App\Http\Controllers\Web\LayananController::class, 'note'])->whereNumber('sub')->middleware('throttle:20,1')->name('portal.layanan.note');
    Route::post('/keluar', [PortalController::class, 'logout'])->name('portal.logout');
});

require __DIR__ . '/admin.php';
