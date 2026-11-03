<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\SettingDefs;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan yang diubah dari dashboard admin (tabel `settings`).
 * Nilai rahasia (API key, server key, kunci lisensi) dienkripsi dengan APP_KEY.
 * Urutan: nilai di database -> default di SettingDefs.
 */
class Settings
{
    /** @var array<string,?string>|null */
    private static ?array $cache = null;

    private static int $version = 0;

    /** Naik setiap kali pengaturan berubah; dipakai untuk membatalkan cache turunan (mis. paket & harga). */
    public static function version(): int { return self::$version; }

    public static function flush(): void { self::$cache = null; self::$version++; }

    private static function load(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (Setting::query()->get() as $row) {
                    $v = $row->value;
                    if ($row->is_secret && $v !== null && $v !== '') {
                        try { $v = Crypt::decryptString($v); } catch (\Throwable) { $v = ''; }   // APP_KEY berganti -> anggap kosong
                    }
                    self::$cache[$row->key] = $v;
                }
            } catch (\Throwable) { /* migrasi belum jalan */ }
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::load();
        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') return $all[$key];
        $def = SettingDefs::all()[$key]['default'] ?? null;
        return $def !== null ? $def : $default;
    }

    /** Nilai mentah tanpa fallback ke default (null/'' bila belum diisi). */
    public static function raw(string $key): ?string
    {
        return self::load()[$key] ?? null;
    }

    public static function bool(string $key): bool { return (string) self::get($key, '0') === '1'; }
    public static function int(string $key, int $default = 0): int { return (int) self::get($key, $default); }
    public static function has(string $key): bool { return (string) (self::load()[$key] ?? '') !== ''; }

    public static function set(string $key, ?string $value, ?bool $secret = null): void
    {
        $secret ??= (SettingDefs::all()[$key]['type'] ?? '') === 'secret';
        $stored = ($secret && $value !== null && $value !== '') ? Crypt::encryptString($value) : $value;
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $stored, 'is_secret' => $secret]);
        self::flush();
    }

    public static function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();
        self::flush();
    }
}
