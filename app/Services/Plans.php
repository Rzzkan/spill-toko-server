<?php

namespace App\Services;

use App\Models\App as AppModel;

/**
 * Paket & harga per aplikasi. Shofee memakai Pengaturan dashboard (kunci plan.*), aplikasi lain memakai kolom `plans` di tabel apps.
 * `original` = harga coret (tampilan saja).
 */
class Plans
{
    public static function all(string $app = AppModel::DEFAULT): array
    {
        if ($app !== AppModel::DEFAULT) return self::forApp($app);
        $p = [
            'monthly' => ['label' => 'Bulanan', 'price' => Settings::int('plan.monthly.price', 24000), 'original' => Settings::int('plan.monthly.original', 49000), 'days' => max(1, Settings::int('plan.monthly.days', 30))],
            'yearly' => ['label' => 'Tahunan', 'price' => Settings::int('plan.yearly.price', 249000), 'original' => Settings::int('plan.yearly.original', 549000), 'days' => max(1, Settings::int('plan.yearly.days', 365))],
        ];
        if (Settings::bool('plan.lifetime.enabled')) {
            $p['lifetime'] = ['label' => 'Lifetime', 'price' => Settings::int('plan.lifetime.price', 200000), 'original' => 0, 'days' => null];
        }
        return $p;
    }

    private static function forApp(string $slug): array
    {
        $cfg = (array) (AppModel::bySlug($slug)?->plans ?? []);
        $out = [];
        foreach (['monthly' => ['Bulanan', 30], 'yearly' => ['Tahunan', 365]] as $k => [$label, $days]) {
            $x = (array) ($cfg[$k] ?? []);
            if ((int) ($x['price'] ?? 0) <= 0) continue;
            $out[$k] = ['label' => $label, 'price' => (int) $x['price'], 'original' => (int) ($x['original'] ?? 0), 'days' => max(1, (int) ($x['days'] ?? $days))];
        }
        $l = (array) ($cfg['lifetime'] ?? []);
        if (!empty($l['enabled']) && (int) ($l['price'] ?? 0) > 0) $out['lifetime'] = ['label' => 'Lifetime', 'price' => (int) $l['price'], 'original' => 0, 'days' => null];
        return $out;
    }

    public static function trialDays(string $app = AppModel::DEFAULT): int
    {
        if ($app !== AppModel::DEFAULT) return max(0, (int) (AppModel::bySlug($app)?->trial_days ?? 3));
        return max(0, Settings::int('trial_days', 3));
    }

    public static function find(string $plan, string $app = AppModel::DEFAULT): ?array { return self::all($app)[$plan] ?? null; }

    /** Persen diskon dari harga coret (0 bila tidak ada). */
    public static function discount(array $p): int
    {
        return ($p['original'] ?? 0) > $p['price'] ? (int) round((1 - $p['price'] / $p['original']) * 100) : 0;
    }
}
