<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLog extends Model
{
    protected $fillable = ['user_id', 'customer_id', 'action', 'note'];

    /** Catat aksi admin yang sedang login. */
    public static function record(string $action, string $note, ?int $customerId = null): self
    {
        return static::query()->create(['user_id' => auth()->id(), 'customer_id' => $customerId, 'action' => $action, 'note' => $note]);
    }

    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
