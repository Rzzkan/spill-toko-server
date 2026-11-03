<?php

namespace App\Models;

use App\Services\AppCatalog;
use Illuminate\Database\Eloquent\Model;

/** Aplikasi di bawah TemanSeller (mis. Shofee). Tiap aplikasi punya trial, paket, dan langganan sendiri. */
class App extends Model
{
    public const DEFAULT = 'shofee';

    protected $table = 'apps';
    protected $fillable = ['slug', 'name', 'tagline', 'icon', 'trial_days', 'plans', 'active', 'sort'];

    protected function casts(): array { return ['plans' => 'array', 'active' => 'boolean', 'trial_days' => 'integer']; }

    protected static function booted(): void
    {
        static::saved(fn () => AppCatalog::flush());
        static::deleted(fn () => AppCatalog::flush());
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
