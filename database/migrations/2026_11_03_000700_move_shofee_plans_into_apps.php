<?php

use App\Services\Settings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Harga & trial Shofee dulu di Pengaturan → Langganan; sekarang disimpan per aplikasi di tabel apps (sama seperti aplikasi lain). */
return new class extends Migration
{
    public function up(): void
    {
        $app = DB::table('apps')->where('slug', 'shofee')->first();
        if (!$app || $app->plans) return;
        $plans = [
            'monthly' => ['price' => Settings::int('plan.monthly.price', 24000), 'original' => Settings::int('plan.monthly.original', 49000), 'days' => max(1, Settings::int('plan.monthly.days', 30))],
            'yearly' => ['price' => Settings::int('plan.yearly.price', 249000), 'original' => Settings::int('plan.yearly.original', 549000), 'days' => max(1, Settings::int('plan.yearly.days', 365))],
            'lifetime' => ['enabled' => Settings::bool('plan.lifetime.enabled'), 'price' => Settings::int('plan.lifetime.price', 200000)],
        ];
        DB::table('apps')->where('slug', 'shofee')->update(['plans' => json_encode($plans), 'trial_days' => max(0, Settings::int('trial_days', 3))]);
    }

    public function down(): void
    {
        // data tetap di apps.plans; tidak ada yang perlu dikembalikan
    }
};
