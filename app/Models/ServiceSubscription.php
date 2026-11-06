<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSubscription extends Model
{
    public const STATUSES = ['pending' => 'Menunggu konfirmasi', 'active' => 'Aktif', 'expired' => 'Berakhir', 'cancelled' => 'Dibatalkan'];

    protected $fillable = ['customer_id', 'service_plan_id', 'plan_name', 'price', 'status', 'starts_at', 'ends_at', 'drive_url', 'customer_note', 'admin_note'];

    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'price' => 'integer']; }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function plan(): BelongsTo { return $this->belongsTo(ServicePlan::class, 'service_plan_id'); }

    /** Status efektif: aktif yang sudah lewat tanggal berakhir dibaca "expired" tanpa menulis ke DB. */
    public function effectiveStatus(): string
    {
        if ($this->status === 'active' && $this->ends_at && $this->ends_at->isPast()) return 'expired';
        return $this->status;
    }

    public function label(): string { return self::STATUSES[$this->effectiveStatus()] ?? $this->status; }

    public function tone(): string
    {
        return ['pending' => 'warning', 'active' => 'success', 'expired' => 'error', 'cancelled' => 'gray'][$this->effectiveStatus()] ?? 'gray';
    }
}
