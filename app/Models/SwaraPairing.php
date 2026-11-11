<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** sid Colab yang sedang aktif untuk 1 pelanggan Swara. Lihat App\Services\Swara. */
class SwaraPairing extends Model
{
    protected $table = 'swara_pairings';
    protected $fillable = ['customer_id', 'sid', 'paired_at'];
    protected function casts(): array { return ['paired_at' => 'datetime']; }
}
