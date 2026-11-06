<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\AiLog;
use App\Models\App as AppModel;
use App\Models\AuditScan;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Marketplace Audit (Shopee): kuota, penguraian tautan, normalisasi data dari extension, pemeriksaan aturan, dan audit AI (kenari.id).
 * Data Shopee dikumpulkan di browser pengguna (extension) — server tidak pernah memegang sesi/cookie Shopee.
 */
class Audit
{
    public const APP = 'audit';

    public static function enabled(): bool { return Settings::bool('audit.enabled'); }
    public static function freeScans(): int { return max(0, Settings::int('audit.free_scans', 10)); }
    /** Batas harian untuk pelanggan berlangganan (0 = tanpa batas). */
    public static function paidDailyCap(): int { return max(0, Settings::int('audit.paid_daily_cap', 100)); }
    public static function retentionHours(): int { return max(1, Settings::int('audit.retention_hours', 24)); }
    public static function extensionUrl(): string { return trim((string) Settings::get('audit.extension_url', '')); }

    private static function tz(): string { return (string) config('temanseller.display_timezone', 'Asia/Jakarta'); }
    private static function monthStart(): Carbon { return now(self::tz())->startOfMonth()->utc(); }
    private static function dayStart(): Carbon { return now(self::tz())->startOfDay()->utc(); }

    // ---------- kuota ----------
    /** @return array{unlimited:bool,limit:?int,used:int,left:?int,resets_at:?int,until:?int,daily_used:int,daily_cap:int,can:bool,reason:string} */
    public static function quota(Customer $c): array
    {
        $a = $c->access(self::APP);
        $unlimited = (bool) $a['access'];
        $used = AuditScan::query()->where('customer_id', $c->id)->where('counted', true)->where('created_at', '>=', self::monthStart())->count();
        $dailyUsed = AuditScan::query()->where('customer_id', $c->id)->where('counted', true)->where('created_at', '>=', self::dayStart())->count();
        $cap = self::paidDailyCap();
        $limit = self::freeScans();
        $can = true;
        $reason = '';
        if (!self::enabled()) { $can = false; $reason = 'Marketplace Audit sedang ditutup.'; }
        elseif ($unlimited && $cap > 0 && $dailyUsed >= $cap) { $can = false; $reason = "Batas harian langganan tercapai ($cap scan/hari). Reset pukul 00:00 WIB."; }
        elseif (!$unlimited && $used >= $limit) { $can = false; $reason = "Jatah $limit scan bulan ini habis. Berlangganan untuk scan tanpa batas, atau tunggu reset bulan depan."; }
        return [
            'unlimited' => $unlimited, 'limit' => $unlimited ? null : $limit, 'used' => $used, 'left' => $unlimited ? null : max(0, $limit - $used),
            'resets_at' => now(self::tz())->startOfMonth()->addMonth()->utc()->getTimestamp(), 'until' => $a['until'] ?? null,
            'daily_used' => $dailyUsed, 'daily_cap' => $cap, 'can' => $can, 'reason' => $reason,
        ];
    }

    // ---------- tautan Shopee ----------
    /** @return array{kind:string,shopid:?int,itemid:?int,username:?string,url:string}|null */
    public static function parseUrl(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '' || strlen($raw) > 500) return null;
        if (!preg_match('#^https?://#i', $raw)) $raw = 'https://' . $raw;
        $p = parse_url($raw);
        if (!$p || empty($p['host']) || !in_array(strtolower($p['scheme'] ?? ''), ['http', 'https'], true)) return null;
        $host = strtolower($p['host']);
        if ($host !== 'shopee.co.id' && $host !== 'www.shopee.co.id') return null;
        $path = rawurldecode((string) ($p['path'] ?? ''));
        $base = 'https://shopee.co.id';
        if (preg_match('#-i\.(\d+)\.(\d+)#', $path, $m) || preg_match('#^/product/(\d+)/(\d+)#', $path, $m)) {
            return ['kind' => 'product', 'shopid' => (int) $m[1], 'itemid' => (int) $m[2], 'username' => null, 'url' => "$base/product/{$m[1]}/{$m[2]}"];
        }
        if (preg_match('#^/shop/(\d+)#', $path, $m)) {
            return ['kind' => 'shop', 'shopid' => (int) $m[1], 'itemid' => null, 'username' => null, 'url' => "$base/shop/{$m[1]}"];
        }
        if (preg_match('#^/([A-Za-z0-9._]{2,40})/?$#', $path, $m) && !in_array(strtolower($m[1]), self::RESERVED, true)) {
            return ['kind' => 'shop', 'shopid' => null, 'username' => $m[1], 'url' => "$base/{$m[1]}"];
        }
        return null;
    }

    private const RESERVED = ['search', 'cart', 'user', 'buyer', 'mall', 'flash_sale', 'daily_discover', 'm', 'verify', 'shopee-mall', 'legacy', 'product', 'shop', 'login', 'categories'];

    // ---------- siklus pemindaian ----------
    /** Buat pemindaian baru. @return array{scan:AuditScan,token:string,target:array} */
    public static function create(Customer $c, string $url): array
    {
        if (!self::enabled()) throw new ApiException('Marketplace Audit sedang ditutup.', 503);
        $target = self::parseUrl($url);
        if (!$target) throw new ApiException('Tautan tidak dikenali. Tempel tautan toko atau produk dari shopee.co.id.', 422);
        self::expireStale($c);
        if (AuditScan::query()->where('customer_id', $c->id)->whereIn('status', AuditScan::ACTIVE)->exists()) {
            throw new ApiException('Masih ada pemindaian yang berjalan. Selesaikan atau batalkan dulu.', 429);
        }
        $q = self::quota($c);
        if (!$q['can']) throw new ApiException($q['reason'], 402);
        $token = Str::random(40);
        $scan = AuditScan::query()->create(['customer_id' => $c->id, 'token_hash' => hash('sha256', $token), 'kind' => $target['kind'], 'url' => $target['url'], 'status' => 'waiting']);
        return ['scan' => $scan, 'token' => $token, 'target' => $target];
    }

    /** Pemindaian yang menggantung > 10 menit dianggap kedaluwarsa (tidak memakai kuota). */
    public static function expireStale(Customer $c): void
    {
        AuditScan::query()->where('customer_id', $c->id)->whereIn('status', ['waiting', 'collected'])->where('updated_at', '<', now()->subMinutes(10))
            ->update(['status' => 'expired', 'error' => 'Waktu habis', 'data' => null]);
        AuditScan::query()->where('customer_id', $c->id)->where('status', 'analyzing')->where('updated_at', '<', now()->subMinutes(4))
            ->update(['status' => 'failed', 'error' => 'Analisis terlalu lama, coba lagi']);
    }

    /** Dipanggil extension (token sekali pakai). */
    public static function receive(AuditScan $scan, string $token, array $in): AuditScan
    {
        if (!hash_equals($scan->token_hash, hash('sha256', $token))) throw new ApiException('Token tidak valid', 403);
        if ($scan->status !== 'waiting') throw new ApiException('Pemindaian ini sudah tidak menunggu data.', 409);
        if ($scan->created_at->lt(now()->subMinutes(10))) {
            $scan->update(['status' => 'expired', 'error' => 'Waktu habis']);
            throw new ApiException('Pemindaian kedaluwarsa. Mulai lagi dari halaman Marketplace Audit.', 410);
        }
        $st = (string) ($in['status'] ?? 'ok');
        if ($st === 'needs_login') { $scan->update(['status' => 'needs_login', 'error' => 'Belum login Shopee']); return $scan; }
        if ($st !== 'ok') {
            $scan->update(['status' => 'failed', 'error' => mb_substr(trim((string) ($in['message'] ?? 'Gagal mengumpulkan data')) ?: 'Gagal mengumpulkan data', 0, 200)]);
            return $scan;
        }
        $data = $scan->kind === 'product' ? self::normalizeProduct($in) : self::normalizeShop($in);
        if ($data === null) {
            $scan->update(['status' => 'failed', 'error' => 'Data dari Shopee tidak terbaca. Coba lagi atau pakai tautan lain.']);
            return $scan;
        }
        $scan->update(['status' => 'collected', 'data' => $data, 'error' => null]);
        return $scan;
    }

    /** Pelanggan melanjutkan setelah login Shopee: kembali ke 'waiting' dengan token baru. */
    public static function rearm(AuditScan $scan): string
    {
        $token = Str::random(40);
        $scan->update(['status' => 'waiting', 'error' => null, 'token_hash' => hash('sha256', $token)]);
        $scan->touch();
        return $token;
    }


    // ---------- unggahan: halaman tersimpan (.html/.mhtml) & screenshot ----------
    public const MAX_PAGES = 2;
    public const MAX_IMAGES = 4;

    public static function visionModel(): string { return trim((string) Settings::get('audit.vision_model', '')); }

    /**
     * Buat pemindaian dari berkas unggahan. Berkas HANYA dibaca di memori pada request ini:
     * teks/gambar dikirim ke AI untuk diambil angkanya, lalu dibuang. Yang tersimpan hanya data terstruktur hasil bacaan.
     */
    public static function createUpload(Customer $c, string $kind, ?string $url, array $pages, array $images): AuditScan
    {
        if (!self::enabled()) throw new ApiException('Marketplace Audit sedang ditutup.', 503);
        if (!in_array($kind, ['product', 'shop'], true)) throw new ApiException('Pilih jenis audit: produk atau toko.', 422);
        $target = null;
        if ($url !== null && trim($url) !== '') {
            $target = self::parseUrl($url);
            if (!$target) throw new ApiException('Tautan tidak dikenali. Kosongkan saja atau tempel tautan dari shopee.co.id.', 422);
            $kind = $target['kind'];
        }
        if (!$pages && !$images) throw new ApiException('Unggah minimal satu halaman tersimpan atau satu screenshot.', 422);
        self::expireStale($c);
        if (AuditScan::query()->where('customer_id', $c->id)->whereIn('status', AuditScan::ACTIVE)->exists()) {
            throw new ApiException('Masih ada audit yang berjalan. Selesaikan atau batalkan dulu.', 429);
        }
        $q = self::quota($c);
        if (!$q['can']) throw new ApiException($q['reason'], 402);
        $scan = AuditScan::query()->create([
            'customer_id' => $c->id, 'token_hash' => hash('sha256', Str::random(40)), 'kind' => $kind, 'url' => $target['url'] ?? null, 'status' => 'analyzing', 'data' => null,
        ]);
        $data = self::extract($scan, ['pages' => $pages, 'images' => $images]);   // berkas mentah hanya hidup di variabel ini
        unset($pages, $images);
        if ($data !== null) $scan->update(['data' => $data, 'status' => 'collected']);
        return $scan->refresh();
    }

    /** Teks yang tampil dari halaman tersimpan (HTML biasa atau .mhtml), tanpa skrip/gaya/gambar. */
    public static function pageText(string $raw): string
    {
        $head = substr($raw, 0, 4000);
        if (stripos($head, 'multipart/related') !== false && ($h = self::mhtmlHtml($raw)) !== null) $raw = $h;
        if (!mb_check_encoding($raw, 'UTF-8')) $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        $meta = [];
        if (preg_match('#<title[^>]*>(.*?)</title>#is', $raw, $m)) $meta[] = 'title: ' . trim(html_entity_decode(strip_tags($m[1])));
        if (preg_match_all('#<meta[^>]+(?:property|name)=["\'](og:title|og:description|description|product:price:amount)["\'][^>]*>#i', $raw, $mm)) {
            foreach ($mm[0] as $i => $tag) if (preg_match('#content=["\']([^"\']*)["\']#i', $tag, $cv)) $meta[] = $mm[1][$i] . ': ' . trim(html_entity_decode($cv[1]));
        }
        if (preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $raw, $ld)) {
            foreach (array_slice($ld[1], 0, 3) as $j) $meta[] = 'json-ld: ' . mb_substr(trim(preg_replace('/\s+/u', ' ', $j) ?? ''), 0, 3000);
        }
        $html = preg_replace('#<(script|style|noscript|svg|template|iframe|head)\b[^>]*>.*?</\1>#is', ' ', $raw) ?? $raw;
        $html = preg_replace('#<(br|/p|/div|/li|/h[1-6]|/tr|/section|/button|/span)\b[^>]*>#i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $out = []; $prev = '';
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');
            if ($line === '' || $line === $prev) continue;
            $out[] = $line; $prev = $line;
        }
        return mb_substr(implode("\n", array_merge($meta, [''], $out)), 0, 16000);
    }

    /** Ambil bagian text/html utama dari berkas MHTML. */
    private static function mhtmlHtml(string $raw): ?string
    {
        if (!preg_match('#boundary="?([^"\r\n;]+)"?#i', substr($raw, 0, 4000), $m)) return null;
        foreach (explode('--' . $m[1], $raw) as $part) {
            $sp = preg_split('/\R\R/', $part, 2);
            if (count($sp) < 2 || !preg_match('#Content-Type:\s*text/html#i', $sp[0])) continue;
            $body = $sp[1];
            if (preg_match('#Content-Transfer-Encoding:\s*quoted-printable#i', $sp[0])) $body = quoted_printable_decode($body);
            elseif (preg_match('#Content-Transfer-Encoding:\s*base64#i', $sp[0])) $body = (string) base64_decode(preg_replace('/\s+/', '', $body), false);
            if (preg_match('#charset="?([A-Za-z0-9_\-]+)#i', $sp[0], $cs) && strtolower($cs[1]) !== 'utf-8') $body = @mb_convert_encoding($body, 'UTF-8', $cs[1]) ?: $body;
            return $body;
        }
        return null;
    }

    /** Perkecil screenshot (maks 1600 px) jadi JPEG base64 agar ringan dikirim ke AI. */
    public static function prepareImage(string $bytes): string
    {
        $info = @getimagesizefromstring($bytes);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) throw new ApiException('Berkas gambar tidak dikenali (pakai JPG, PNG, atau WEBP).', 422);
        if (!function_exists('imagecreatefromstring')) {
            if (strlen($bytes) > 900_000) throw new ApiException('Gambar terlalu besar. Kecilkan ukurannya (maks 900 KB).', 422);
            return base64_encode($bytes);
        }
        $im = @imagecreatefromstring($bytes);
        if (!$im) throw new ApiException('Gambar tidak bisa dibaca.', 422);
        [$w, $h] = [imagesx($im), imagesy($im)];
        $scale = min(1, 1600 / max($w, $h));
        if ($scale < 1) { $r = imagescale($im, max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))); if ($r) $im = $r; }
        ob_start(); imagejpeg($im, null, 80); $jpg = (string) ob_get_clean();
        return base64_encode($jpg);
    }

    private const EXTRACT_SYS = 'Kamu asisten ekstraksi data. Tugasmu membaca teks halaman Shopee Indonesia dan/atau screenshot, lalu menyalin angka dan teks yang BENAR-BENAR tampil. Jangan menebak atau mengarang; isi null jika tidak terlihat. Balas HANYA JSON valid tanpa markdown.';

    private static function extractPrompt(string $kind, array $pages): string
    {
        $num = 'Semua angka ditulis sebagai angka polos tanpa pemisah ribuan dan tanpa "Rp" (contoh Rp54.000 -> 54000, "2,4rb" -> 2400, "1,2jt" -> 1200000, "4,9" -> 4.9).';
        $spec = $kind === 'product'
            ? '{"product":{"name","price" (harga setelah diskon),"price_min","price_max","discount_pct","stock","sold" (jumlah terjual yang tampil),"historical_sold","rating" (0-5),"rating_count","rating_dist" (array 5 angka jumlah ulasan bintang 1..5),"liked_count","images" (jumlah foto di galeri jika terlihat),"has_video" (true/false),"description" (teks deskripsi),"category","brand","attributes" (jumlah baris spesifikasi),"variations" (jumlah pilihan variasi),"free_shipping","cod","wholesale","flash_sale" (true/false),"shop":{"name","rating","followers","response_rate" (persen),"response_time","location"}},"reviews":[{"rating","text"}] (maks 10)}'
            : '{"shop":{"name","username","rating","rating_count","followers","response_rate" (persen),"response_time","joined_days" (hari sejak bergabung; "3 tahun lalu" -> 1095),"product_count","is_official","is_mall","description","location"},"products":[{"name","price","sold","historical_sold","rating","rating_count","discount_pct","stock"}] (produk yang tampil, maks 30)}';
        $txt = '';
        foreach ($pages as $i => $p) $txt .= "\n<halaman_" . ($i + 1) . ">\n" . $p . "\n</halaman_" . ($i + 1) . ">";
        return 'Ini ' . ($kind === 'product' ? 'halaman PRODUK' : 'halaman TOKO') . " Shopee. Ekstrak ke JSON dengan struktur persis: $spec\n$num Hanya isi field yang terlihat jelas.\n" . ($txt !== '' ? "Teks halaman:$txt" : 'Tidak ada teks halaman, baca dari screenshot yang dilampirkan.');
    }

    /** Konversi angka gaya Indonesia pada field numerik hasil ekstraksi ("54.000", "2,4rb", "1,2jt"). */
    private static function idNumbers(mixed $v, ?string $key = null): mixed
    {
        if (is_array($v)) { foreach ($v as $k => $x) $v[$k] = self::idNumbers($x, is_string($k) ? $k : $key); return $v; }
        if (!is_string($v) || in_array($key, ['name', 'description', 'text', 'username', 'category', 'brand', 'location', 'response_time'], true)) return $v;
        $s = strtolower(trim(str_replace(['Rp', 'rp', ' '], '', $v)));
        if (!preg_match('/^(\d{1,3}(?:\.\d{3})+|\d+(?:[.,]\d+)?)(rb|ribu|k|jt|juta)?\+?%?$/', $s, $m)) return $v;
        $n = $m[1];
        $n = preg_match('/^\d{1,3}(?:\.\d{3})+$/', $n) ? str_replace('.', '', $n) : str_replace(',', '.', $n);
        $mul = match ($m[2] ?? '') { 'rb', 'ribu', 'k' => 1000, 'jt', 'juta' => 1_000_000, default => 1 };
        $f = (float) $n * $mul;
        return $f == floor($f) ? (int) $f : $f;
    }

    /** Langkah ekstraksi: data mentah unggahan -> struktur data audit. Mengembalikan null bila gagal (scan ditandai failed). */
    private static function extract(AuditScan $scan, array $raw): ?array
    {
        if (!Ai::on()) { $scan->update(['status' => 'failed', 'error' => 'AI gagal: analisis AI belum diaktifkan admin, unggahan belum bisa dibaca.']); return null; }
        $pages = array_values(array_filter((array) ($raw['pages'] ?? []), 'is_string'));
        $images = array_values(array_filter((array) ($raw['images'] ?? []), 'is_string'));
        $prompt = self::extractPrompt($scan->kind, $pages);
        $content = $prompt;
        if ($images) {
            $content = [['type' => 'text', 'text' => $prompt]];
            foreach ($images as $b) $content[] = ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,' . $b]];
        }
        $main = trim((string) Settings::get('ai.model', 'deepseek-v4-flash')) ?: 'deepseek-v4-flash';
        $models = array_values(array_unique(array_filter($images ? [self::visionModel() ?: $main, trim((string) Settings::get('ai.fallback_model', ''))] : [$main, trim((string) Settings::get('ai.fallback_model', ''))])));
        $msgs = [['role' => 'system', 'content' => self::EXTRACT_SYS], ['role' => 'user', 'content' => $content]];
        $err = 'tidak diketahui';
        foreach ($models as $m) {
            $r = Ai::chat($msgs, $m);
            self::log($scan->customer_id, $m, $r);
            if (!$r['ok']) { $err = $r['err']; continue; }
            $j = Ai::parseJson($r['content']);
            if (!$j) { $err = 'jawaban AI tidak terbaca'; continue; }
            $j = self::idNumbers($j);
            $d = $scan->kind === 'product' ? self::normalizeProduct($j) : self::normalizeShop($j);
            if ($d !== null) return $d;
            $scan->update(['status' => 'failed', 'error' => 'Data tidak terbaca dari berkas. Pastikan halaman termuat penuh sebelum disimpan, atau coba screenshot lain. Kuota tidak terpakai.']);
            return null;
        }
        $scan->update(['status' => 'failed', 'error' => mb_substr('AI gagal: ' . $err . '. Berkas tidak disimpan, jadi unggah ulang ya (kuota tidak terpakai).', 0, 250)]);
        return null;
    }

    // ---------- normalisasi (daftar putih, nilai dibatasi) ----------
    private static function num(mixed $v, float $min = 0, float $max = 1e12): ?float
    {
        if (is_string($v)) $v = str_replace([',', ' '], ['.', ''], $v);
        if (!is_numeric($v)) return null;
        $f = (float) $v;
        return is_finite($f) ? max($min, min($max, $f)) : null;
    }
    private static function int(mixed $v, int $max = 1000000000): ?int { $n = self::num($v, 0, $max); return $n === null ? null : (int) round($n); }
    private static function str(mixed $v, int $max = 200): ?string
    {
        if (!is_string($v) && !is_numeric($v)) return null;
        $s = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $v)) ?? '');
        return $s === '' ? null : mb_substr($s, 0, $max);
    }
    private static function bool(mixed $v): ?bool { return $v === null ? null : filter_var($v, FILTER_VALIDATE_BOOLEAN); }
    private static function compact(array $a): array { return array_filter($a, fn ($x) => $x !== null && $x !== ''); }

    public static function normalizeProduct(array $in): ?array
    {
        $p = is_array($in['product'] ?? null) ? $in['product'] : null;
        if (!$p || self::str($p['name'] ?? null) === null) return null;
        $shop = is_array($p['shop'] ?? null) ? $p['shop'] : [];
        $reviews = [];
        foreach (array_slice(is_array($in['reviews'] ?? null) ? $in['reviews'] : [], 0, 20) as $r) {
            if (!is_array($r)) continue;
            $rv = self::compact(['rating' => self::int($r['rating'] ?? null, 5), 'text' => self::str($r['text'] ?? null, 240)]);
            if ($rv) $reviews[] = $rv;
        }
        $dist = [];
        if (is_array($p['rating_dist'] ?? null)) foreach (array_slice(array_values($p['rating_dist']), 0, 5) as $d) $dist[] = self::int($d) ?? 0;
        return [
            'product' => self::compact([
                'name' => self::str($p['name'], 300), 'price' => self::num($p['price'] ?? null), 'price_min' => self::num($p['price_min'] ?? null), 'price_max' => self::num($p['price_max'] ?? null),
                'discount_pct' => self::num($p['discount_pct'] ?? null, 0, 100), 'stock' => self::int($p['stock'] ?? null), 'sold' => self::int($p['sold'] ?? null), 'historical_sold' => self::int($p['historical_sold'] ?? null),
                'rating' => self::num($p['rating'] ?? null, 0, 5), 'rating_count' => self::int($p['rating_count'] ?? null), 'rating_dist' => $dist ?: null, 'liked_count' => self::int($p['liked_count'] ?? null),
                'images' => self::int($p['images'] ?? null, 100), 'has_video' => self::bool($p['has_video'] ?? null), 'description' => self::str($p['description'] ?? null, 1500), 'description_len' => self::int($p['description_len'] ?? (is_string($p['description'] ?? null) ? mb_strlen($p['description']) : null), 100000),
                'category' => self::str($p['category'] ?? null, 160), 'brand' => self::str($p['brand'] ?? null, 80), 'attributes' => self::int($p['attributes'] ?? null, 200), 'variations' => self::int($p['variations'] ?? null, 500),
                'free_shipping' => self::bool($p['free_shipping'] ?? null), 'cod' => self::bool($p['cod'] ?? null), 'wholesale' => self::bool($p['wholesale'] ?? null), 'flash_sale' => self::bool($p['flash_sale'] ?? null),
                'shop' => self::compact(['name' => self::str($shop['name'] ?? null, 100), 'rating' => self::num($shop['rating'] ?? null, 0, 5), 'followers' => self::int($shop['followers'] ?? null), 'response_rate' => self::num($shop['response_rate'] ?? null, 0, 100), 'is_official' => self::bool($shop['is_official'] ?? null), 'is_mall' => self::bool($shop['is_mall'] ?? null)]) ?: null,
            ]),
            'reviews' => $reviews,
        ];
    }

    public static function normalizeShop(array $in): ?array
    {
        $s = is_array($in['shop'] ?? null) ? $in['shop'] : null;
        if (!$s || self::str($s['name'] ?? null) === null) return null;
        $products = [];
        foreach (array_slice(is_array($in['products'] ?? null) ? $in['products'] : [], 0, 30) as $x) {
            if (!is_array($x) || self::str($x['name'] ?? null) === null) continue;
            $products[] = self::compact(['name' => self::str($x['name'], 200), 'price' => self::num($x['price'] ?? null), 'sold' => self::int($x['sold'] ?? null), 'historical_sold' => self::int($x['historical_sold'] ?? null),
                'rating' => self::num($x['rating'] ?? null, 0, 5), 'rating_count' => self::int($x['rating_count'] ?? null), 'discount_pct' => self::num($x['discount_pct'] ?? null, 0, 100), 'stock' => self::int($x['stock'] ?? null)]);
        }
        return [
            'shop' => self::compact(['name' => self::str($s['name'], 100), 'username' => self::str($s['username'] ?? null, 60), 'rating' => self::num($s['rating'] ?? null, 0, 5), 'rating_count' => self::int($s['rating_count'] ?? null),
                'followers' => self::int($s['followers'] ?? null), 'response_rate' => self::num($s['response_rate'] ?? null, 0, 100), 'response_time' => self::str($s['response_time'] ?? null, 60), 'joined_days' => self::int($s['joined_days'] ?? null, 20000),
                'product_count' => self::int($s['product_count'] ?? null), 'is_official' => self::bool($s['is_official'] ?? null), 'is_mall' => self::bool($s['is_mall'] ?? null), 'description' => self::str($s['description'] ?? null, 1000),
                'description_len' => self::int($s['description_len'] ?? (is_string($s['description'] ?? null) ? mb_strlen($s['description']) : null), 100000), 'location' => self::str($s['location'] ?? null, 80)]),
            'products' => $products,
        ];
    }

    // ---------- pemeriksaan aturan (tanpa AI) ----------
    private static function chk(string $area, string $st, string $title, string $detail, int $weight = 1): array
    {
        return ['area' => $area, 'status' => $st, 'title' => $title, 'detail' => $detail, 'w' => $weight];
    }

    /** @return array{checks:array,score:int} */
    public static function rules(string $kind, array $d): array
    {
        $c = $kind === 'product' ? self::productRules($d['product'] ?? []) : self::shopRules($d['shop'] ?? [], $d['products'] ?? []);
        $max = 0; $got = 0;
        foreach ($c as $x) {
            $max += $x['w'] * 2;
            $got += $x['w'] * ['ok' => 2, 'warn' => 1, 'bad' => 0][$x['status']];
        }
        return ['checks' => array_map(fn ($x) => array_diff_key($x, ['w' => 1]), $c), 'score' => $max ? (int) round($got / $max * 100) : 0];
    }

    private static function productRules(array $p): array
    {
        $o = [];
        $len = mb_strlen((string) ($p['name'] ?? ''));
        $o[] = $len >= 60 && $len <= 120 ? self::chk('Judul', 'ok', 'Panjang judul pas', "$len karakter (ideal 60–120).", 2)
            : ($len >= 40 && $len <= 140 ? self::chk('Judul', 'warn', 'Judul bisa dioptimalkan', "$len karakter. Ideal 60–120 berisi kata kunci utama + atribut.", 2)
            : self::chk('Judul', 'bad', $len < 40 ? 'Judul terlalu pendek' : 'Judul terlalu panjang', "$len karakter. Ideal 60–120 karakter.", 2));
        if (isset($p['images'])) $o[] = $p['images'] >= 6 ? self::chk('Visual', 'ok', 'Foto produk lengkap', "{$p['images']} foto.", 2)
            : ($p['images'] >= 3 ? self::chk('Visual', 'warn', 'Tambah foto produk', "{$p['images']} foto. Targetkan 6–9 (sudut, detail, ukuran, pemakaian).", 2) : self::chk('Visual', 'bad', 'Foto terlalu sedikit', "{$p['images']} foto saja.", 2));
        if (isset($p['has_video'])) $o[] = $p['has_video'] ? self::chk('Visual', 'ok', 'Ada video produk', 'Video membantu konversi.') : self::chk('Visual', 'warn', 'Belum ada video produk', 'Tambahkan video singkat 15–30 detik.');
        if (isset($p['description_len'])) $o[] = $p['description_len'] >= 500 ? self::chk('Deskripsi', 'ok', 'Deskripsi cukup detail', "{$p['description_len']} karakter.", 2)
            : ($p['description_len'] >= 200 ? self::chk('Deskripsi', 'warn', 'Deskripsi bisa lebih lengkap', "{$p['description_len']} karakter. Targetkan 500+ (spesifikasi, manfaat, cara pakai, FAQ).", 2) : self::chk('Deskripsi', 'bad', 'Deskripsi terlalu singkat', "{$p['description_len']} karakter.", 2));
        if (isset($p['attributes'])) $o[] = $p['attributes'] >= 5 ? self::chk('Spesifikasi', 'ok', 'Atribut produk terisi', "{$p['attributes']} atribut.") : self::chk('Spesifikasi', 'warn', 'Lengkapi atribut produk', "Baru {$p['attributes']} atribut; atribut lengkap membantu pencarian & filter.");
        if (isset($p['rating']) && ($p['rating_count'] ?? 0) >= 5) $o[] = $p['rating'] >= 4.7 ? self::chk('Reputasi', 'ok', 'Rating sangat baik', "{$p['rating']} dari {$p['rating_count']} ulasan.", 2)
            : ($p['rating'] >= 4.3 ? self::chk('Reputasi', 'warn', 'Rating perlu dijaga', "{$p['rating']} dari {$p['rating_count']} ulasan.", 2) : self::chk('Reputasi', 'bad', 'Rating rendah', "{$p['rating']} dari {$p['rating_count']} ulasan. Cek keluhan berulang.", 2));
        if (isset($p['rating_count'])) $o[] = $p['rating_count'] >= 50 ? self::chk('Reputasi', 'ok', 'Ulasan cukup banyak', "{$p['rating_count']} ulasan.") : ($p['rating_count'] >= 10 ? self::chk('Reputasi', 'warn', 'Ulasan masih sedikit', "{$p['rating_count']} ulasan. Dorong pembeli menilai.") : self::chk('Reputasi', 'bad', 'Hampir tanpa ulasan', "{$p['rating_count']} ulasan."));
        if (isset($p['stock'])) $o[] = $p['stock'] <= 0 ? self::chk('Stok', 'bad', 'Stok habis', 'Produk kehilangan penjualan & peringkat.', 2) : ($p['stock'] < 10 ? self::chk('Stok', 'warn', 'Stok menipis', "Sisa {$p['stock']}.") : self::chk('Stok', 'ok', 'Stok aman', "Sisa {$p['stock']}."));
        if (isset($p['variations'])) $o[] = $p['variations'] >= 2 ? self::chk('Penawaran', 'ok', 'Ada pilihan variasi', "{$p['variations']} variasi.") : self::chk('Penawaran', 'warn', 'Belum ada variasi', 'Variasi (ukuran/warna/paket) menaikkan peluang beli.');
        if (isset($p['free_shipping'])) $o[] = $p['free_shipping'] ? self::chk('Penawaran', 'ok', 'Gratis ongkir', 'Mendukung konversi.') : self::chk('Penawaran', 'warn', 'Belum gratis ongkir', 'Ikut program gratis ongkir bisa menaikkan klik & pembelian.');
        if (isset($p['discount_pct'])) $o[] = self::chk('Penawaran', $p['discount_pct'] > 0 ? 'ok' : 'warn', $p['discount_pct'] > 0 ? 'Ada diskon' : 'Tanpa diskon', $p['discount_pct'] > 0 ? "Diskon {$p['discount_pct']}%." : 'Coba voucher toko atau diskon terbatas.');
        $sh = $p['shop'] ?? [];
        if (isset($sh['response_rate'])) $o[] = $sh['response_rate'] >= 90 ? self::chk('Toko', 'ok', 'Balasan chat bagus', "{$sh['response_rate']}%.") : self::chk('Toko', 'warn', 'Tingkat balasan chat rendah', "{$sh['response_rate']}%. Targetkan ≥ 90%.");
        return $o;
    }

    private static function shopRules(array $s, array $products): array
    {
        $o = [];
        if (isset($s['rating'])) $o[] = $s['rating'] >= 4.7 ? self::chk('Reputasi', 'ok', 'Rating toko sangat baik', "{$s['rating']}.", 2) : ($s['rating'] >= 4.3 ? self::chk('Reputasi', 'warn', 'Rating toko perlu dijaga', "{$s['rating']}.", 2) : self::chk('Reputasi', 'bad', 'Rating toko rendah', "{$s['rating']}.", 2));
        if (isset($s['response_rate'])) $o[] = $s['response_rate'] >= 90 ? self::chk('Layanan', 'ok', 'Balasan chat bagus', "{$s['response_rate']}%.", 2) : ($s['response_rate'] >= 70 ? self::chk('Layanan', 'warn', 'Balasan chat bisa lebih cepat', "{$s['response_rate']}%.", 2) : self::chk('Layanan', 'bad', 'Balasan chat rendah', "{$s['response_rate']}%.", 2));
        if (isset($s['followers'])) $o[] = $s['followers'] >= 1000 ? self::chk('Audiens', 'ok', 'Pengikut cukup banyak', number_format($s['followers']) . ' pengikut.') : ($s['followers'] >= 100 ? self::chk('Audiens', 'warn', 'Pengikut masih sedikit', number_format($s['followers']) . ' pengikut. Pakai Shopee Live/feed & voucher follow.') : self::chk('Audiens', 'bad', 'Pengikut sangat sedikit', number_format($s['followers']) . ' pengikut.'));
        if (isset($s['product_count'])) $o[] = $s['product_count'] >= 20 ? self::chk('Katalog', 'ok', 'Katalog cukup luas', "{$s['product_count']} produk.") : ($s['product_count'] >= 8 ? self::chk('Katalog', 'warn', 'Katalog bisa diperluas', "{$s['product_count']} produk.") : self::chk('Katalog', 'bad', 'Katalog sempit', "{$s['product_count']} produk."));
        if (isset($s['description_len'])) $o[] = $s['description_len'] >= 150 ? self::chk('Profil', 'ok', 'Deskripsi toko terisi', "{$s['description_len']} karakter.") : self::chk('Profil', 'warn', 'Deskripsi toko kurang', 'Jelaskan keunggulan, jam operasional, kebijakan retur.');
        if (isset($s['is_official']) || isset($s['is_mall'])) $o[] = self::chk('Profil', ($s['is_official'] ?? false) || ($s['is_mall'] ?? false) ? 'ok' : 'warn', ($s['is_official'] ?? false) || ($s['is_mall'] ?? false) ? 'Toko resmi/Mall' : 'Belum Star/Mall', 'Badge toko meningkatkan kepercayaan.');
        if ($products) {
            $n = count($products);
            $zero = count(array_filter($products, fn ($x) => ($x['historical_sold'] ?? $x['sold'] ?? 0) == 0));
            $o[] = $zero / $n <= .2 ? self::chk('Katalog', 'ok', 'Hampir semua produk pernah terjual', "$zero dari $n produk belum terjual.") : ($zero / $n <= .5 ? self::chk('Katalog', 'warn', 'Banyak produk belum terjual', "$zero dari $n produk belum terjual.", 2) : self::chk('Katalog', 'bad', 'Mayoritas produk belum terjual', "$zero dari $n produk belum terjual.", 2));
            $short = count(array_filter($products, fn ($x) => mb_strlen($x['name']) < 40));
            $o[] = $short / $n <= .2 ? self::chk('Judul', 'ok', 'Judul produk umumnya memadai', "$short dari $n judul < 40 karakter.") : self::chk('Judul', 'warn', 'Banyak judul produk terlalu pendek', "$short dari $n judul < 40 karakter.");
            $rated = array_filter($products, fn ($x) => ($x['rating_count'] ?? 0) >= 5 && isset($x['rating']));
            if ($rated) { $avg = round(array_sum(array_column($rated, 'rating')) / count($rated), 2); $o[] = $avg >= 4.7 ? self::chk('Reputasi', 'ok', 'Rating produk rata-rata baik', "$avg.") : self::chk('Reputasi', $avg >= 4.3 ? 'warn' : 'bad', 'Rating produk rata-rata perlu naik', "$avg."); }
        }
        return $o;
    }

    // ---------- audit AI ----------
    private const SYS = 'Kamu auditor marketplace senior untuk seller Shopee Indonesia. Tugasmu menilai toko/produk dari data yang diberikan dan memberi rekomendasi yang praktis, spesifik, dan bisa langsung dikerjakan. '
        . 'Pakai Bahasa Indonesia santai-profesional. Jangan mengarang angka yang tidak ada di data. Jika data tidak ada, katakan tidak diketahui. Balas HANYA JSON valid tanpa markdown.';

    private static function prompt(string $kind, array $data, array $rules): string
    {
        $what = $kind === 'product' ? 'produk' : 'toko';
        return "Audit $what Shopee ini. Hasil pemeriksaan aturan otomatis (sudah dihitung, gunakan sebagai konteks): " . json_encode($rules['checks'], JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Keluarkan JSON dengan kunci: \"ringkasan\" (2-3 kalimat), \"skor\" (0-100), \"label\" (Sangat Potensial|Potensial|Biasa|Berisiko), \"temuan\" (array string: kekuatan & kelemahan utama), \"peluang\" (array string), \"risiko\" (array string), "
            . "\"rekomendasi\" (array {aksi, alasan, prioritas: tinggi|sedang|rendah}, minimal 5, urut dari yang paling berdampak), \"kata_kunci\" (array 8-15 kata kunci), \"saran_judul\" (array minimal 5 judul alternatif berkarakter 60-120" . ($kind === 'product' ? ', berdasarkan judul asli' : ', untuk produk andalan toko') . ").\n\n"
            . "Data hasil pengumpulan (JSON):\n<data>\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n</data>";
    }

    /** Jalankan audit penuh untuk scan berstatus collected/failed (data ada). Mengembalikan scan yang sudah diperbarui. */
    public static function analyze(AuditScan $scan): AuditScan
    {
        @set_time_limit(200);
        // klaim atomik: satu permintaan saja yang boleh menganalisis
        $claimed = AuditScan::query()->whereKey($scan->id)->whereIn('status', ['collected', 'failed'])->whereNotNull('data')->update(['status' => 'analyzing', 'error' => null, 'updated_at' => now()]);
        if (!$claimed) return $scan->refresh();
        $scan->refresh();
        $data = $scan->data;
        $rules = self::rules($scan->kind, $data);
        $result = ['kind' => $scan->kind, 'rules' => $rules['checks'], 'rule_score' => $rules['score'], 'ai' => null, 'score' => $rules['score'], 'label' => self::label($rules['score']), 'summary' => '', 'subject' => self::subject($scan->kind, $data)];

        if (Ai::on()) {
            $models = array_values(array_unique(array_filter([trim((string) Settings::get('ai.model', 'deepseek-v4-flash')) ?: 'deepseek-v4-flash', trim((string) Settings::get('ai.fallback_model', ''))])));
            $last = null;
            $msgs = [['role' => 'system', 'content' => self::SYS], ['role' => 'user', 'content' => self::prompt($scan->kind, $data, $rules)]];
            foreach ($models as $m) {
                $r = Ai::chat($msgs, $m);
                self::log($scan->customer_id, $m, $r);
                if ($r['ok']) { $j = Ai::parseJson($r['content']); if ($j) { $last = Ai::normalize($j) + ['model' => $r['model']]; break; } $r['err'] = 'Jawaban AI tidak terbaca'; }
                $last = ['err' => $r['err']];
            }
            if (!isset($last['ringkasan'])) {
                $scan->update(['status' => 'failed', 'error' => mb_substr('AI gagal: ' . ($last['err'] ?? 'tidak diketahui') . '. Datanya masih tersimpan sementara — tekan "Coba lagi" (kuota tidak terpakai).', 0, 250)]);
                return $scan;
            }
            $result['ai'] = $last;
            $result['summary'] = $last['ringkasan'];
            $result['score'] = (int) round($rules['score'] * 0.6 + $last['skor'] * 0.4);
            $result['label'] = self::label($result['score']);
        } else {
            $result['summary'] = 'Hasil ini berdasarkan pemeriksaan otomatis. Analisis AI belum diaktifkan oleh admin.';
        }
        $scan->update(['status' => 'done', 'counted' => true, 'result' => $result, 'error' => null]);
        return $scan;
    }

    private static function label(int $s): string { return $s >= 80 ? 'Sangat Potensial' : ($s >= 60 ? 'Potensial' : ($s >= 40 ? 'Biasa' : 'Berisiko')); }

    private static function subject(string $kind, array $d): array
    {
        return $kind === 'product' ? ['name' => $d['product']['name'] ?? '', 'sub' => $d['product']['shop']['name'] ?? ''] : ['name' => $d['shop']['name'] ?? '', 'sub' => '@' . ($d['shop']['username'] ?? '')];
    }

    private static function log(int $customerId, string $model, array $r): void
    {
        try {
            AiLog::query()->create(['customer_id' => $customerId, 'kind' => 'audit', 'cache_hit' => false, 'model' => mb_substr($model, 0, 80), 'tokens_in' => $r['tin'], 'tokens_out' => $r['tout'], 'ms' => $r['ms'],
                'status' => $r['ok'] ? 'ok' : 'error', 'error' => mb_substr((string) $r['err'], 0, 250), 'created_at' => now()]);
        } catch (\Throwable $e) { logger()->warning('audit_ai_log: ' . $e->getMessage()); }
    }

    /** Hapus tautan/data/hasil setelah masa simpan; baris hitungan kuota tetap. */
    public static function purge(): int
    {
        $cut = now()->subHours(self::retentionHours());
        return AuditScan::query()->whereNull('purged_at')->where('updated_at', '<', $cut)->whereNotIn('status', ['waiting', 'collected', 'analyzing'])
            ->update(['url' => null, 'data' => null, 'result' => null, 'purged_at' => now()])
            + AuditScan::query()->whereNull('purged_at')->where('updated_at', '<', now()->subHours(max(1, self::retentionHours())))->whereIn('status', ['waiting', 'collected', 'analyzing'])
                ->update(['status' => 'expired', 'url' => null, 'data' => null, 'result' => null, 'purged_at' => now()]);
    }
}
