<?php

use App\Models\AiCache;
use App\Models\AiLog;
use Illuminate\Support\Facades\Schedule;

// Pembersihan harian (butuh cron: * * * * * php artisan schedule:run)
Schedule::call(function () {
    AiLog::query()->where('created_at', '<', now()->subDays(90))->delete();
    AiCache::query()->where('created_at', '<', now()->subDays(30))->delete();
})->dailyAt('03:15')->name('spill-cleanup');

// Hapus tautan/data/hasil Marketplace Audit setelah masa simpan (hanya hitungan kuota yang tersisa)
Schedule::call(fn () => \App\Services\Audit::purge())->hourly()->name('audit-purge');
