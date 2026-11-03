<?php

namespace App\Services;

use App\Models\App as AppModel;
use Illuminate\Support\Collection;

/**
 * Daftar aplikasi + paketnya, di-cache selama satu request (atribut request, jadi aman di worker dan antar tes).
 * Tabel apps hanya dibaca sekali per request, bukan sekali per pemanggilan Plans/bySlug.
 * Cache dibersihkan otomatis saat model App disimpan/dihapus, dan paket Shofee mengikuti versi Settings.
 */
class AppCatalog
{
    private static function store(): \ArrayObject
    {
        $req = request();
        $s = $req->attributes->get('_app_catalog');
        if (!$s instanceof \ArrayObject) { $s = new \ArrayObject(); $req->attributes->set('_app_catalog', $s); }
        return $s;
    }

    public static function remember(string $key, callable $fn): mixed
    {
        $s = self::store();
        if (!isset($s[$key])) $s[$key] = ['v' => $fn()];
        return $s[$key]['v'];
    }

    public static function flush(): void { self::store()->exchangeArray([]); }

    /** @return Collection<int,AppModel> semua aplikasi, urut tampil */
    public static function all(): Collection
    {
        return self::remember('all', fn () => AppModel::query()->orderBy('sort')->orderBy('id')->get());
    }

    public static function active(): Collection { return self::all()->where('active', true)->values(); }

    public static function find(string $slug): ?AppModel { return self::all()->firstWhere('slug', $slug); }

    /** @return array<string,string> slug => nama */
    public static function names(): array { return self::all()->pluck('name', 'slug')->all(); }

    /** Paket per aplikasi (hasil Plans::build), di-cache; berubah bila Pengaturan diubah. */
    public static function plans(string $slug, callable $build): array
    {
        return self::remember("plans.$slug." . Settings::version(), $build);
    }

    /** Harga "mulai dari" per bulan (paket berjangka termurah per 30 hari) atau null bila tidak ada paket berjangka. */
    public static function monthlyFrom(string $slug): ?int
    {
        $per = array_map(fn ($p) => $p['price'] / max(1, $p['days'] / 30), array_filter(Plans::all($slug), fn ($p) => !empty($p['days']) && (int) $p['price'] > 0));
        return $per ? (int) round(min($per)) : null;
    }

    /** Ringkasan satu aplikasi untuk kartu (hub, akun): model, trial, harga mulai, paket. */
    public static function summary(AppModel $a): array
    {
        return ['app' => $a, 'trial' => Plans::trialDays($a->slug), 'from' => self::monthlyFrom($a->slug), 'plans' => Plans::all($a->slug)];
    }
}
