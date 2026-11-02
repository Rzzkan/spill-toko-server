<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['customer_id', 'kind', 'cache_hit', 'model', 'tokens_in', 'tokens_out', 'ms', 'status', 'error', 'created_at'];

    protected function casts(): array { return ['cache_hit' => 'boolean', 'created_at' => 'datetime']; }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}
