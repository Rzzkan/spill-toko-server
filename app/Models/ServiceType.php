<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'sort', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function plans(): HasMany { return $this->hasMany(ServicePlan::class)->orderBy('sort')->orderBy('id'); }
}
