<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePlan extends Model
{
    protected $fillable = ['service_type_id', 'name', 'tagline', 'price', 'original_price', 'period', 'features', 'addon', 'badge', 'is_featured', 'is_active', 'duration_days', 'sort'];

    protected function casts(): array { return ['is_featured' => 'boolean', 'is_active' => 'boolean', 'price' => 'integer', 'original_price' => 'integer']; }

    /** Persen hemat bila ada harga coret yang lebih tinggi dari harga jual. */
    public function discountPct(): int
    {
        return $this->original_price > $this->price && $this->price > 0 ? (int) round((1 - $this->price / $this->original_price) * 100) : 0;
    }

    public function type(): BelongsTo { return $this->belongsTo(ServiceType::class, 'service_type_id'); }

    /** @return string[] */
    public function featureList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->features) ?: [])));
    }

    /** Paket yang tampil & bisa dipesan: aktif, jenisnya aktif, dan sudah berharga. */
    public static function orderable()
    {
        return static::query()->where('is_active', true)->where('price', '>', 0)
            ->whereHas('type', fn ($q) => $q->where('is_active', true))->with('type')->orderBy('sort')->orderBy('id');
    }
}
