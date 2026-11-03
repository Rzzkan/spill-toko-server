<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** Format tampilan di dashboard (zona waktu WIB). */
class Fmt
{
    public static function rp(int|float|null $n): string { return 'Rp' . number_format((float) $n, 0, ',', '.'); }

    public static function dt(?CarbonInterface $t, bool $time = true): string
    {
        return $t ? $t->copy()->setTimezone(config('spill.display_timezone'))->format($time ? 'd M Y H:i' : 'd M Y') : '—';
    }

    public static function status(array $access): array
    {
        return match ($access['plan']) {
            'lifetime' => ['Lifetime', 'brand'],
            'monthly' => [($access['tier'] ?? 'monthly') === 'yearly' ? 'Tahunan' : 'Bulanan', 'success'],
            'trial' => ['Trial', 'warning'],
            'none' => ['Belum dipakai', 'gray'],
            default => ['Berakhir', 'error'],
        };
    }

    public static function orderTone(string $s): string
    {
        return ['paid' => 'success', 'pending' => 'warning'][$s] ?? 'error';
    }
}
