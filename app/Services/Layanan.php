<?php

namespace App\Services;

/** Pengaturan & tautan layanan kelola Instagram/Threads (nilai dari Pengaturan → Layanan Sosmed). */
class Layanan
{
    public static function enabled(): bool { return Settings::bool('layanan.enabled'); }

    public static function waNumber(): string { return preg_replace('/\D+/', '', (string) Settings::get('layanan.wa_number', '')) ?? ''; }

    /** Tautan wa.me dengan pesan awal; null bila nomor belum diisi admin. */
    public static function waUrl(?string $message = null): ?string
    {
        $n = self::waNumber();
        if ($n === '') return null;
        return 'https://wa.me/' . $n . '?text=' . rawurlencode($message ?? (string) Settings::get('layanan.wa_template', ''));
    }

    public static function paymentInfo(): string { return trim((string) Settings::get('layanan.payment_info', '')); }

    /** Hanya tautan https yang boleh dijadikan link (cegah javascript:). */
    public static function safeUrl(?string $u): ?string
    {
        $u = trim((string) $u);
        return preg_match('#^https://[^\s]+$#i', $u) ? $u : null;
    }
}
