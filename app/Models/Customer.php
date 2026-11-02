<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['google_sub', 'email', 'name', 'picture', 'trial_ends_at', 'paid_until', 'lifetime', 'last_login_at'];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime', 'paid_until' => 'datetime', 'last_login_at' => 'datetime', 'lifetime' => 'boolean'];
    }

    /** Buat / perbarui pelanggan dari klaim token Google yang sudah terverifikasi (dipakai extension & web). */
    public static function fromGoogle(array $g): self
    {
        $c = static::query()->where('google_sub', $g['sub'])->first();
        if (!$c) {
            return static::query()->create(['google_sub' => $g['sub'], 'email' => $g['email'], 'name' => $g['name'] ?? null, 'picture' => $g['picture'] ?? null,
                'trial_ends_at' => now()->addDays(\App\Services\Plans::trialDays()), 'lifetime' => false, 'last_login_at' => now()]);
        }
        $c->update(['email' => $g['email'], 'name' => $g['name'] ?? $c->name, 'picture' => $g['picture'] ?? $c->picture, 'last_login_at' => now()]);
        return $c;
    }

    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function aiLogs(): HasMany { return $this->hasMany(AiLog::class); }

    /** @return array{plan:string,access:bool,until:?int} until = unix timestamp */
    public function access(): array
    {
        $now = time();
        $paid = $this->paid_until?->getTimestamp() ?? 0;
        $trial = $this->trial_ends_at?->getTimestamp() ?? 0;
        if ($this->lifetime) return ['plan' => 'lifetime', 'access' => true, 'until' => null];
        if ($paid > $now) return ['plan' => 'monthly', 'access' => true, 'until' => $paid];
        if ($trial > $now) return ['plan' => 'trial', 'access' => true, 'until' => $trial];
        return ['plan' => 'expired', 'access' => false, 'until' => max($paid, $trial) ?: null];
    }

    public function scopeStatus($q, ?string $status)
    {
        $now = now();
        return match ($status) {
            'lifetime' => $q->where('lifetime', true),
            'monthly' => $q->where('lifetime', false)->where('paid_until', '>', $now),
            'trial' => $q->where('lifetime', false)->where(fn ($w) => $w->whereNull('paid_until')->orWhere('paid_until', '<=', $now))->where('trial_ends_at', '>', $now),
            'expired' => $q->where('lifetime', false)->where(fn ($w) => $w->whereNull('paid_until')->orWhere('paid_until', '<=', $now))->where('trial_ends_at', '<=', $now),
            default => $q,
        };
    }
}
