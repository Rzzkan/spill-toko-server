<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLog extends Model
{
    protected $fillable = ['user_id', 'customer_id', 'action', 'note'];

    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
