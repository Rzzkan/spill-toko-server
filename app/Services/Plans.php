<?php

namespace App\Services;

/** Paket & harga (bisa diubah dari dashboard lewat Settings). `original` = harga coret (tampilan saja). */
class Plans
{
    public static function all(): array
    {
        $p = [
            'monthly' => ['label' => 'Bulanan', 'price' => Settings::int('plan.monthly.price', 24000), 'original' => Settings::int('plan.monthly.original', 49000), 'days' => max(1, Settings::int('plan.monthly.days', 30))],
            'yearly' => ['label' => 'Tahunan', 'price' => Settings::int('plan.yearly.price', 249000), 'original' => Settings::int('plan.yearly.original', 549000), 'days' => max(1, Settings::int('plan.yearly.days', 365))],
        ];
        if (Settings::bool('plan.lifetime.enabled')) {
            $p['lifetime'] = ['label' => 'Lifetime', 'price' => Settings::int('plan.lifetime.price', 200000), 'original' => 0, 'days' => null];
        }
        return $p;
    }

    public static function trialDays(): int { return max(0, Settings::int('trial_days', 3)); }

    public static function find(string $plan): ?array { return self::all()[$plan] ?? null; }

    /** Persen diskon dari harga coret (0 bila tidak ada). */
    public static function discount(array $p): int
    {
        return ($p['original'] ?? 0) > $p['price'] ? (int) round((1 - $p['price'] / $p['original']) * 100) : 0;
    }
}
