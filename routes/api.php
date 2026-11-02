<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Middleware\CustomerAuth;
use Illuminate\Support\Facades\Route;

// Gaya lama yang dipakai extension: /api/index.php?r=plans|auth|me|checkout|orders|ai/analyze|midtrans/notify
Route::match(['GET', 'POST', 'OPTIONS'], '/index.php', [ApiController::class, 'gateway']);

// Rute bersih (setara)
Route::get('/plans', [ApiController::class, 'plans']);
Route::post('/auth', [ApiController::class, 'auth']);
Route::post('/midtrans/notify', [ApiController::class, 'notify']);
Route::middleware(CustomerAuth::class)->group(function () {
    Route::get('/me', [ApiController::class, 'me']);
    Route::post('/checkout', [ApiController::class, 'checkout']);
    Route::get('/orders', [ApiController::class, 'orders']);
    Route::post('/ai/analyze', [ApiController::class, 'analyze']);
});
