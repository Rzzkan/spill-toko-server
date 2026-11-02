<?php

namespace App\Services;

/** Paket & harga (bisa diubah dari dashboard lewat Settings). */
class Plans
{
    public static function all(): array
    {
        return [
            'monthly' => ['label' => 'Bulanan', 'price' => Settings::int('plan.monthly.price', 25000), 'days' => max(1, Settings::int('plan.monthly.days', 30))],
            'lifetime' => ['label' => 'Lifetime', 'price' => Settings::int('plan.lifetime.price', 200000), 'days' => null],
        ];
    }

    public static function trialDays(): int { return max(0, Settings::int('trial_days', 7)); }

    public static function find(string $plan): ?array { return self::all()[$plan] ?? null; }
}
