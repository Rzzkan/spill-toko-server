<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ServicePortfolio extends Model
{
    protected $fillable = ['title', 'client_name', 'platform', 'description', 'result_text', 'image_path', 'link_url', 'sort', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function imageUrl(): ?string
    {
        return $this->image_path ? route('layanan.image', [$this->id, substr(md5((string) $this->updated_at), 0, 8)]) : null;
    }

    public function deleteImage(): void
    {
        if ($this->image_path) Storage::disk('local')->delete($this->image_path);
    }
}
