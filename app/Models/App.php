<?php

namespace App\Models;

use App\Services\AppCatalog;
use Illuminate\Database\Eloquent\Model;

/** Aplikasi di bawah TemanSeller (mis. Shofee). Tiap aplikasi punya trial, paket, dan langganan sendiri. */
class App extends Model
{
    public const DEFAULT = 'shofee';

    protected $table = 'apps';
    protected $fillable = ['slug', 'name', 'tagline', 'url', 'download_url', 'icon', 'trial_days', 'plans', 'active', 'sort'];

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
        // Marketplace Audit: tujuan selalu halaman penjelasannya; login baru diminta saat klik Coba/Mulai/Berlangganan.
        if ($this->slug === 'audit' && ($u === '' || preg_match('#^(https?://[^/]+)?/akun(/|$)#', $u))) return url('/marketplace-audit');
        if ($u !== '') return preg_match('#^https?://#i', $u) ? $u : url('/' . ltrim($u, '/'));
        if ($this->slug === self::DEFAULT) return \App\Http\Controllers\Web\PublicController::shofeeUrl();
        return url('/akun/aplikasi/' . $this->slug);
    }

    /**
     * Alamat tombol "buka/pakai aplikasi" di halaman akun — beda dari landingUrl() (itu halaman promosi publik).
     * Extension Chrome (ada `download_url`) -> langsung ke Chrome Web Store. Aplikasi web dengan halaman alat sendiri
     * (Swara, Marketplace Audit) -> langsung ke halaman alatnya. Lainnya -> halaman paket (belum punya halaman alat).
     */
    public function toolUrl(): string
    {
        $dl = trim((string) $this->download_url);
        if ($dl !== '') return $dl;
        return match ($this->slug) {
            'swara' => route('swara.page'),
            'audit' => route('audit'),
            default => route('portal.app', $this->slug),
        };
    }

    /** True bila tombol "buka aplikasi" mengarah ke tempat lain (Chrome Web Store), bukan halaman kami sendiri. */
    public function toolUrlExternal(): bool
    {
        return trim((string) $this->download_url) !== '';
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
