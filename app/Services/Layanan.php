<?php

namespace App\Services;

/** Pengaturan & tautan layanan kelola Instagram/Threads (nilai dari Pengaturan → Layanan Sosmed). */
class Layanan
{
    public static function enabled(): bool { return Settings::bool('layanan.enabled'); }

    /** Layanan yang segera hadir (mis. "Kelola TikTok"), dipisah koma di Pengaturan. Hanya info, tidak bisa dipesan. */
    public static function comingSoon(): array
    {
        return self::splitList((string) Settings::get('layanan.coming_soon', ''));
    }

    /** Layanan segera hadir yang tampil di dropdown menu Layanan (Pengaturan → "Segera hadir di menu"). */
    public static function comingSoonMenu(): array
    {
        return self::splitList((string) Settings::get('layanan.coming_soon_menu', ''));
    }

    /** Gabungan semua layanan segera hadir (menu + halaman), tanpa duplikat. Dipakai slideshow beranda. */
    public static function comingSoonAll(): array
    {
        return array_values(array_unique(array_merge(self::comingSoon(), self::comingSoonMenu())));
    }

    private static function splitList(string $v): array
    {
        return array_values(array_filter(array_map(fn ($x) => mb_substr(trim($x), 0, 40), explode(',', $v))));
    }

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

    // ---------- kuesioner client (Google Form) ----------
    public static function formUrl(): ?string { return self::safeUrl((string) Settings::get('layanan.form_url', '')); }

    /** Ringkasan pesanan untuk pesan WhatsApp pertama, supaya admin langsung paham konteksnya. */
    public static function orderSummary(\App\Models\Customer $c, string $plan, int|float $price, ?string $note = null, ?string $status = null, bool $questionnaire = false): string
    {
        $l = ['Halo TemanSeller, ini ringkasan pesanan saya:', '',
            'Nama: ' . ($c->name ?: '-'), 'Email akun: ' . $c->email,
            'Paket: ' . $plan, 'Harga: Rp' . number_format((float) $price, 0, ',', '.')];
        if ($status) $l[] = 'Status: ' . $status;
        $l[] = 'Catatan: ' . (trim((string) $note) !== '' ? trim(preg_replace('/\s+/', ' ', (string) $note)) : '-');
        if ($questionnaire) $l[] = 'Kuesioner: sudah saya isi lewat Google Form';
        $l[] = ''; $l[] = 'Saya ingin bertanya / melanjutkan.';
        return implode("\n", $l);
    }
}
