<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** Langganan satu pelanggan pada satu aplikasi. */
class Subscription extends Model
{
    protected $fillable = ['customer_id', 'app', 'trial_ends_at', 'paid_until', 'paid_plan', 'lifetime'];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime', 'paid_until' => 'datetime', 'lifetime' => 'boolean'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    /** @return array{plan:string,access:bool,until:?int,tier?:string} until = unix timestamp */
    public function access(): array
    {
        $now = time();
        $paid = $this->paid_until?->getTimestamp() ?? 0;
        $trial = $this->trial_ends_at?->getTimestamp() ?? 0;
        if ($this->lifetime) return ['plan' => 'lifetime', 'access' => true, 'until' => null];
        if ($paid > $now) return ['plan' => 'monthly', 'access' => true, 'until' => $paid, 'tier' => $this->paid_plan ?: 'monthly'];
        if ($trial > $now) return ['plan' => 'trial', 'access' => true, 'until' => $trial];
        return ['plan' => 'expired', 'access' => false, 'until' => max($paid, $trial) ?: null];
    }

    /** Tambah masa aktif berbayar (menyambung dari masa aktif yang masih berjalan). */
    public function extend(int $days, string $plan): void
    {
        $base = max(time(), $this->paid_until?->getTimestamp() ?? 0);
        $this->update(['paid_until' => Carbon::createFromTimestampUTC($base + $days * 86400), 'paid_plan' => $plan]);
    }
}
