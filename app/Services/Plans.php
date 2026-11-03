<?php

namespace App\Services;

use App\Models\App as AppModel;

/**
 * Paket & harga per aplikasi, semuanya disimpan di kolom `plans` tabel apps (diatur di Admin → Aplikasi → Ubah).
 * `original` = harga coret (tampilan saja).
 */
class Plans
{
    public static function all(string $app = AppModel::DEFAULT): array
    {
        return AppCatalog::plans($app, fn () => self::build($app));
    }

    private static function build(string $app): array
    {
        return self::forApp($app);
    }

    /** Paket bawaan Shofee bila kolom `plans` belum terisi (mis. sebelum migrasi data). */
    private const SHOFEE_DEFAULT = [
        'monthly' => ['price' => 24000, 'original' => 49000, 'days' => 30],
        'yearly' => ['price' => 249000, 'original' => 549000, 'days' => 365],
        'lifetime' => ['enabled' => false, 'price' => 200000],
    ];

    private static function forApp(string $slug): array
    {
        $cfg = (array) (AppCatalog::find($slug)?->plans ?? []);
        if (!$cfg && $slug === AppModel::DEFAULT) $cfg = self::SHOFEE_DEFAULT;
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
        return max(0, (int) (AppCatalog::find($app)?->trial_days ?? 3));
    }

    public static function find(string $plan, string $app = AppModel::DEFAULT): ?array { return self::all($app)[$plan] ?? null; }

    /** Persen diskon dari harga coret (0 bila tidak ada). */
    public static function discount(array $p): int
    {
        return ($p['original'] ?? 0) > $p['price'] ? (int) round((1 - $p['price'] / $p['original']) * 100) : 0;
    }
}
