<?php

namespace App\Models;

use App\Models\App as AppModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Langganan satu pelanggan pada satu aplikasi. */
class Subscription extends Model
{
    protected $fillable = ['customer_id', 'app', 'trial_ends_at', 'paid_until', 'paid_plan', 'lifetime'];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime', 'paid_until' => 'datetime', 'lifetime' => 'boolean'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    /** Akses untuk pelanggan yang belum pernah memakai aplikasi (tidak ada baris langganan). */
    public static function none(): array { return ['plan' => 'none', 'access' => false, 'until' => null]; }

    /**
     * Jumlah pelanggan per status untuk satu aplikasi dalam SATU query (sama dengan Customer::scopeStatus).
     * @return array{trial:int,monthly:int,lifetime:int,expired:int}
     */
    public static function statusCounts(string $app = AppModel::DEFAULT): array
    {
        $now = now()->toDateTimeString();
        $unpaid = '(paid_until IS NULL OR paid_until <= ?)';
        $row = DB::table('subscriptions')->where('app', $app)->selectRaw(
            "COALESCE(SUM(CASE WHEN lifetime = 1 THEN 1 ELSE 0 END), 0) AS lifetime,
             COALESCE(SUM(CASE WHEN lifetime = 0 AND paid_until > ? THEN 1 ELSE 0 END), 0) AS monthly,
             COALESCE(SUM(CASE WHEN lifetime = 0 AND $unpaid AND trial_ends_at > ? THEN 1 ELSE 0 END), 0) AS trial,
             COALESCE(SUM(CASE WHEN lifetime = 0 AND $unpaid AND (trial_ends_at IS NULL OR trial_ends_at <= ?) THEN 1 ELSE 0 END), 0) AS expired",
            [$now, $now, $now, $now, $now])->first();
        return ['trial' => (int) $row->trial, 'monthly' => (int) $row->monthly, 'lifetime' => (int) $row->lifetime, 'expired' => (int) $row->expired];
    }

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

    /** Tambah masa aktif berbayar; menyambung dari akhir paket berbayar ATAU sisa trial yang masih berjalan (mana yang lebih lama). */
    public function extend(int $days, string $plan): void
    {
        $base = max(time(), $this->paid_until?->getTimestamp() ?? 0, $this->trial_ends_at?->getTimestamp() ?? 0);
        $this->update(['paid_until' => Carbon::createFromTimestampUTC($base + $days * 86400), 'paid_plan' => $plan]);
    }
}
