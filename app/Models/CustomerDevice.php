<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu browser (instalasi extension) yang terikat ke akun. Maksimal config('temanseller.max_devices') per akun. */
class CustomerDevice extends Model
{
    protected $fillable = ['customer_id', 'device_hash', 'label', 'ip', 'last_seen_at'];

    protected function casts(): array { return ['last_seen_at' => 'datetime']; }

    public static function hash(string $deviceId): string { return hash('sha256', $deviceId); }

    public static function labelFrom(string $name, string $ua): string
    {
        $name = trim(preg_replace('/[^\p{L}\p{N} ._·()\-]/u', '', $name));
        if ($name !== '') return mb_substr($name, 0, 100);
        $b = str_contains($ua, 'Edg/') ? 'Edge' : (str_contains($ua, 'OPR/') ? 'Opera' : (str_contains($ua, 'Chrome/') ? 'Chrome' : 'Browser'));
        $os = str_contains($ua, 'Windows') ? 'Windows' : (str_contains($ua, 'Mac OS') ? 'macOS' : (str_contains($ua, 'Android') ? 'Android' : (str_contains($ua, 'Linux') ? 'Linux' : '')));
        return trim($b . ($os ? ' · ' . $os : ''));
    }
}
