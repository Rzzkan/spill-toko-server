<?php

namespace App\Models;

use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['google_sub', 'email', 'name', 'picture', 'trial_ends_at', 'paid_until', 'paid_plan', 'lifetime', 'last_login_at'];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime', 'paid_until' => 'datetime', 'last_login_at' => 'datetime', 'lifetime' => 'boolean'];
    }

    /** Buat / perbarui pelanggan dari klaim token Google yang sudah terverifikasi (dipakai extension & web). */
    public static function fromGoogle(array $g): self
    {
        $c = static::query()->where('google_sub', $g['sub'])->first();
        if (!$c) {
            $c = static::query()->create(['google_sub' => $g['sub'], 'email' => $g['email'], 'name' => $g['name'] ?? null, 'picture' => $g['picture'] ?? null,
                'trial_ends_at' => now()->addDays(\App\Services\Plans::trialDays()), 'lifetime' => false, 'last_login_at' => now()]);
            $c->subscription(App::DEFAULT, true);   // langganan Shofee (trial) dibuat saat mendaftar
            return $c;
        }
        $c->update(['email' => $g['email'], 'name' => $g['name'] ?? $c->name, 'picture' => $g['picture'] ?? $c->picture, 'last_login_at' => now()]);
        return $c;
    }

    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function aiLogs(): HasMany { return $this->hasMany(AiLog::class); }
    public function devices(): HasMany { return $this->hasMany(CustomerDevice::class); }

    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }

    /**
     * Langganan untuk satu aplikasi. Membaca TIDAK menulis ke database:
     *  - Shofee tanpa baris langganan (pelanggan lama) dibaca dari kolom lama di tabel customers (objek belum tersimpan);
     *  - aplikasi lain tanpa baris = null (belum pernah dipakai).
     * Dengan $create = true baris dibuat (Shofee: salinan kolom lama; aplikasi lain: trial baru), untuk dipakai saat login/bayar/admin mengubah.
     * Bila relasi `subscriptions` sudah dimuat (with('subscriptions')), tidak ada query tambahan.
     */
    public function subscription(string $app = App::DEFAULT, bool $create = false): ?Subscription
    {
        $s = $this->relationLoaded('subscriptions') ? $this->subscriptions->firstWhere('app', $app) : $this->subscriptions()->where('app', $app)->first();
        if ($s) return $s;
        $attrs = $app === App::DEFAULT
            ? ['trial_ends_at' => $this->trial_ends_at, 'paid_until' => $this->paid_until, 'paid_plan' => $this->paid_plan, 'lifetime' => (bool) $this->lifetime]
            : ($create ? ['trial_ends_at' => now()->addDays(\App\Services\Plans::trialDays($app)), 'lifetime' => false] : null);
        if ($attrs === null) return null;
        if (!$create) return (new Subscription($attrs))->forceFill(['customer_id' => $this->id, 'app' => $app]);   // belum tersimpan
        $s = $this->subscriptions()->create(['app' => $app] + $attrs);
        $this->unsetRelation('subscriptions');
        return $s;
    }

    /** @return array{plan:string,access:bool,until:?int,tier?:string} until = unix timestamp; plan 'none' = belum pernah memakai aplikasi */
    public function access(string $app = App::DEFAULT): array
    {
        return $this->subscription($app)?->access() ?? Subscription::none();
    }

    /** Pastikan langganan aktif; bila tidak, lempar ApiException 402 (dipakai endpoint API & AI). */
    public function requireAccess(string $app = App::DEFAULT, string $message = 'Akses berakhir'): array
    {
        $a = $this->access($app);
        if (!$a['access']) throw new ApiException($message, 402);
        return $a;
    }

    public function scopeStatus($q, ?string $status, string $app = App::DEFAULT)
    {
        if (!in_array($status, ['lifetime', 'monthly', 'trial', 'expired'], true)) return $q;
        $now = now();
        return $q->whereHas('subscriptions', function ($s) use ($status, $app, $now) {
            $s->where('app', $app);
            $unpaid = fn ($w) => $w->whereNull('paid_until')->orWhere('paid_until', '<=', $now);
            match ($status) {
                'lifetime' => $s->where('lifetime', true),
                'monthly' => $s->where('lifetime', false)->where('paid_until', '>', $now),
                'trial' => $s->where('lifetime', false)->where($unpaid)->where('trial_ends_at', '>', $now),
                'expired' => $s->where('lifetime', false)->where($unpaid)->where(fn ($w) => $w->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', $now)),
            };
        });
    }
}
