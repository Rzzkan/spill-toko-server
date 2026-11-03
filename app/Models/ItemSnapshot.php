<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSnapshot extends Model
{
    protected $fillable = ['item_key', 'day', 'sold', 'm30', 'price'];
}
