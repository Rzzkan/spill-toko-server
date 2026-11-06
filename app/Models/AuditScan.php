<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu kali audit (toko atau produk Shopee). Baris ini jadi penghitung kuota; kolom url/data/result dihapus otomatis
 * setelah masa simpan (lihat Audit::purge) sehingga tersisa hanya jejak hitungan.
 */
class AuditScan extends Model
{
    public const ACTIVE = ['waiting', 'collected', 'analyzing'];

    protected $fillable = ['customer_id', 'token_hash', 'kind', 'url', 'status', 'error', 'counted', 'data', 'result', 'purged_at'];
    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['counted' => 'boolean', 'data' => 'array', 'result' => 'array', 'purged_at' => 'datetime'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    public function isActive(): bool { return in_array($this->status, self::ACTIVE, true); }
}
