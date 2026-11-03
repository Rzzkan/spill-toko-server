<?php

namespace App\Models;

use App\Services\AppCatalog;
use Illuminate\Database\Eloquent\Model;

/** Aplikasi di bawah TemanSeller (mis. Shofee). Tiap aplikasi punya trial, paket, dan langganan sendiri. */
class App extends Model
{
    public const DEFAULT = 'shofee';

    protected $table = 'apps';
    protected $fillable = ['slug', 'name', 'tagline', 'url', 'icon', 'trial_days', 'plans', 'active', 'sort'];

    protected function casts(): array { return ['plans' => 'array', 'active' => 'boolean', 'trial_days' => 'integer']; }

    protected static function booted(): void
    {
        static::saved(fn () => AppCatalog::flush());
        static::deleted(fn () => AppCatalog::flush());
    }

    /**
     * Alamat halaman aplikasi (tombol di landing). Urutan: kolom `url` dari admin (alamat penuh atau path seperti /shofee),
     * lalu SHOFEE_URL untuk Shofee, lalu /shofee atau halaman akun aplikasi.
     */
    public function landingUrl(): string
    {
        $u = trim((string) $this->url);
        if ($u !== '') return preg_match('#^https?://#i', $u) ? $u : url('/' . ltrim($u, '/'));
        if ($this->slug === self::DEFAULT) return \App\Http\Controllers\Web\PublicController::shofeeUrl();
        return url('/akun/aplikasi/' . $this->slug);
    }

    public static function slugOk(string $slug): bool { return (bool) preg_match('/^[a-z][a-z0-9\-]{1,29}$/', $slug); }

    public static function bySlug(string $slug): ?self { return AppCatalog::find($slug); }

    /** Aplikasi aktif, urut tampil. */
    public static function listAll()
    {
        return AppCatalog::all();
    }

    public static function listActive()
    {
        return AppCatalog::active();
    }
}
