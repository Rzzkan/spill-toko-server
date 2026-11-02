<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCache extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'hash';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['hash', 'kind', 'model', 'result', 'hits', 'created_at'];

    protected function casts(): array { return ['created_at' => 'datetime']; }
}
