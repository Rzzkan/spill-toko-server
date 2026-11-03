<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = ['order_id', 'customer_id', 'app', 'plan', 'amount', 'status', 'snap_token', 'payment_type', 'raw_notification', 'paid_at'];

    protected function casts(): array { return ['paid_at' => 'datetime', 'amount' => 'integer']; }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}
