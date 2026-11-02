<?php

use App\Services\Plans;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('public.home', [
    'trial' => Plans::trialDays(), 'monthly' => Plans::all()['monthly']['price'], 'lifetime' => Plans::all()['lifetime']['price'],
    'store' => Settings::get('app.chrome_store_url', ''),
]));
Route::get('/thanks', fn (Request $r) => view('public.thanks', ['order' => preg_replace('/[^A-Za-z0-9\-]/', '', (string) $r->query('order'))]));

require __DIR__ . '/admin.php';
