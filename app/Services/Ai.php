<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\AiCache;
use App\Models\AiLog;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;

/**
 * Analisis AI lewat kenari.id (kompatibel OpenAI Chat Completions). Semua pengaturan dari dashboard (Settings).
 * Alur: akses aktif -> cache -> laju & kuota -> model utama (+ cadangan) -> rapikan JSON -> simpan cache.
 */
class Ai
{
    public const KINDS = ['product', 'shop', 'search'];
    private const PROMPT_VERSION = 3;   // naikkan bila skema keluaran berubah agar cache lama tidak terpakai

    public static function key(): string { return (string) Settings::get('ai.api_key', ''); }
    public static function on(): bool { return Settings::bool('ai.enabled') && self::key() !== ''; }

    private static function dayStart(): \Illuminate\Support\Carbon
    {
        return now(config('spill.display_timezone'))->startOfDay()->utc();   // 00:00 WIB
    }

    /** Kuota analisis AI per hari untuk satu jenis paket (trial|monthly|yearly|lifetime). null = tanpa batas. */
    public static function quota(string $kind): ?int
    {
        $defaults = ['trial' => 20, 'monthly' => 100, 'yearly' => 150, 'lifetime' => 100];
        if (!isset($defaults[$kind])) return 0;
        $v = Settings::int("ai.quota.$kind", $defaults[$kind]);
        return $v <= 0 ? null : $v;
    }

    /** Semua kuota untuk ditampilkan di landing/portal/extension. */
    public static function quotas(): array
    {
        return ['trial' => self::quota('trial'), 'monthly' => self::quota('monthly'), 'yearly' => self::quota('yearly'), 'lifetime' => self::quota('lifetime')];
    }

    public static function limitFor(Customer $c): ?int
    {
        $a = $c->access();
        $kind = $a['plan'] === 'monthly' && ($a['tier'] ?? '') === 'yearly' ? 'yearly' : $a['plan'];
        return self::quota($kind);
    }

    public static function usedToday(Customer $c): int
    {
        return AiLog::query()->where('customer_id', $c->id)->where('cache_hit', false)->where('status', 'ok')
            ->where('created_at', '>=', self::dayStart())->count();
    }

    public static function info(Customer $c): array
    {
        $on = self::on();
        return [
            'enabled' => $on,
            'auto' => $on && Settings::bool('ai.auto'),
            'used' => $on ? self::usedToday($c) : 0,
            'limit' => $on ? self::limitFor($c) : 0,
            'quotas' => $on ? self::quotas() : null,
        ];
    }

    // ---------- pembersihan data masukan ----------
    public static function clean(mixed $v, int $depth = 0, int $maxArr = 40): mixed
    {
        if (is_int($v) || is_float($v)) return is_finite((float) $v) ? $v : null;
        if (is_bool($v) || $v === null) return $v;
        if (is_string($v)) return mb_substr(trim(preg_replace('/\s+/u', ' ', $v) ?? ''), 0, 220);
        if (is_array($v) && $depth < 4) {
            $o = [];
            $i = 0;
            $isList = array_is_list($v);
            foreach ($v as $k => $x) {
                if ($i++ >= $maxArr) break;
                $c = self::clean($x, $depth + 1, $maxArr);
                if ($c === null || $c === '' || $c === []) continue;
                if ($isList) $o[] = $c; else $o[mb_substr((string) $k, 0, 40)] = $c;
            }
            return $o;
        }
        return null;
    }

    // ---------- panggilan kenari.id ----------
    public static function chat(array $messages, string $model): array
    {
        $base = rtrim((string) Settings::get('ai.base_url', 'https://kenari.id/v1'), '/');
        $timeout = max(10, min(180, Settings::int('ai.timeout', 70)));
        $json = Settings::bool('ai.json_mode');
        $body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) Settings::get('ai.temperature', '0.3'),
            'max_tokens' => max(300, min(8000, Settings::int('ai.max_tokens', 2200))),
        ];
        $send = function (array $b) use ($base, $timeout) {
            try {
                return Http::withToken(self::key())->acceptJson()->asJson()->timeout($timeout)->post($base . '/chat/completions', $b);
            } catch (\Throwable) { return null; }
        };
        $t0 = microtime(true);
        $r = $send($json ? $body + ['response_format' => ['type' => 'json_object']] : $body);
        if ($json && $r && in_array($r->status(), [400, 422], true)) $r = $send($body);   // model tidak mendukung response_format
        $ms = (int) round((microtime(true) - $t0) * 1000);

        $st = $r?->status() ?? 0;
        $j = $r?->json();
        $content = (string) ($j['choices'][0]['message']['content'] ?? '');
        $res = ['ok' => false, 'status' => $st, 'ms' => $ms, 'model' => (string) ($j['model'] ?? $model),
            'tin' => (int) ($j['usage']['prompt_tokens'] ?? 0), 'tout' => (int) ($j['usage']['completion_tokens'] ?? 0), 'content' => $content, 'err' => ''];
        if ($st === 200 && $content !== '') { $res['ok'] = true; return $res; }
        $msg = $j['error']['message'] ?? (is_string($j['error'] ?? null) ? $j['error'] : ($j['message'] ?? ''));
        $res['err'] = match (true) {
            $st === 402 => 'Saldo kenari.id tidak cukup',
            $st === 401 || $st === 403 => 'API key kenari.id ditolak',
            $st === 429 => 'Kena batas laju kenari.id',
            $st === 0 => 'Tidak bisa menghubungi kenari.id (timeout)',
            default => 'kenari.id HTTP ' . $st . ($msg !== '' ? ': ' . mb_substr((string) $msg, 0, 160) : ''),
        };
        return $res;
    }

    /** Tes koneksi (dipakai tombol di dashboard). @return array{0:bool,1:string} */
    public static function test(): array
    {
        if (self::key() === '') return [false, 'API key belum diisi.'];
        $r = self::chat([['role' => 'user', 'content' => 'Balas dengan satu kata saja: OK']], (string) Settings::get('ai.model'));
        return $r['ok'] ? [true, 'Terhubung ✓ model ' . $r['model'] . ' · ' . $r['ms'] . ' ms · jawaban: ' . mb_substr(trim($r['content']), 0, 60)] : [false, 'Gagal: ' . $r['err']];
    }

    /** Daftar model dari kenari.id (publik). @return array{0:bool,1:string|array} */
    public static function fetchModels(): array
    {
        $base = rtrim((string) Settings::get('ai.base_url'), '/');
        if ($base === '') return [false, 'Base URL kenari.id belum diisi.'];
        try {
            $req = Http::acceptJson()->timeout(20);
            if (self::key() !== '') $req = $req->withToken(self::key());
            $r = $req->get($base . '/models');
        } catch (\Throwable $e) { return [false, 'Tidak bisa menghubungi ' . $base . ' — ' . \Illuminate\Support\Str::limit($e->getMessage(), 120)]; }
        $ids = collect($r->json('data') ?? [])->pluck('id')->filter()->map('strval')->sort()->values()->all();
        if ($r->ok() && $ids) return [true, $ids];
        $hint = $r->status() === 401 || $r->status() === 403 ? ' — API key salah/belum diisi.' : ($r->status() === 404 ? ' — periksa Base URL (biasanya berakhiran /v1).' : '.');
        return [false, 'Gagal memuat daftar model (HTTP ' . $r->status() . ')' . $hint];
    }

    public static function parseJson(string $s): ?array
    {
        $s = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($s)) ?? '');
        $a = strpos($s, '{');
        $b = strrpos($s, '}');
        if ($a === false || $b === false || $b <= $a) return null;
        $j = json_decode(substr($s, $a, $b - $a + 1), true);
        return is_array($j) ? $j : null;
    }

    private static function list(mixed $v, int $max = 12): array
    {
        if (!is_array($v)) return [];
        $o = [];
        foreach ($v as $x) {
            if (is_array($x)) $x = $x['teks'] ?? $x['text'] ?? implode(' - ', array_filter(array_map('strval', array_filter($x, 'is_scalar'))));
            $x = trim((string) $x);
            if ($x !== '') $o[] = mb_substr($x, 0, 400);
            if (count($o) >= $max) break;
        }
        return $o;
    }

    public static function normalize(array $j): array
    {
        $rec = [];
        foreach (is_array($j['rekomendasi'] ?? null) ? $j['rekomendasi'] : [] as $x) {
            if (is_string($x)) $x = ['aksi' => $x];
            if (!is_array($x)) continue;
            $aksi = trim((string) ($x['aksi'] ?? $x['judul'] ?? ''));
            if ($aksi === '') continue;
            $p = strtolower((string) ($x['prioritas'] ?? 'sedang'));
            $rec[] = ['aksi' => mb_substr($aksi, 0, 300), 'alasan' => mb_substr(trim((string) ($x['alasan'] ?? '')), 0, 400),
                'prioritas' => in_array($p, ['tinggi', 'sedang', 'rendah'], true) ? $p : 'sedang'];
            if (count($rec) >= 12) break;
        }
        $skor = (int) max(0, min(100, round((float) ($j['skor'] ?? 0))));
        $label = (string) ($j['label'] ?? '');
        if (!in_array($label, ['Sangat Potensial', 'Potensial', 'Biasa', 'Berisiko'], true)) {
            $label = $skor >= 80 ? 'Sangat Potensial' : ($skor >= 60 ? 'Potensial' : ($skor >= 40 ? 'Biasa' : 'Berisiko'));
        }
        return [
            'ringkasan' => mb_substr(trim((string) ($j['ringkasan'] ?? '')), 0, 900),
            'skor' => $skor, 'label' => $label,
            'temuan' => self::list($j['temuan'] ?? null), 'peluang' => self::list($j['peluang'] ?? null), 'risiko' => self::list($j['risiko'] ?? null),
            'rekomendasi' => $rec,
            'kata_kunci' => self::list($j['kata_kunci'] ?? null, 20), 'saran_judul' => self::list($j['saran_judul'] ?? null, 8),
        ];
    }

    private static function log(Customer $c, string $kind, bool $hit, string $model, int $tin, int $tout, int $ms, string $status, string $err = ''): void
    {
        try {
            AiLog::query()->create(['customer_id' => $c->id, 'kind' => $kind, 'cache_hit' => $hit, 'model' => mb_substr($model, 0, 80),
                'tokens_in' => $tin, 'tokens_out' => $tout, 'ms' => $ms, 'status' => $status, 'error' => mb_substr($err, 0, 250), 'created_at' => now()]);
        } catch (\Throwable $e) { logger()->warning('ai_log: ' . $e->getMessage()); }
    }

    /** @return array{analysis:array,cached:bool,generated_at:int,ai:array} */
    public static function analyze(Customer $c, array $in): array
    {
        @set_time_limit(200);
        $c->requireAccess(message: 'Langganan berakhir. Perpanjang untuk memakai analisis AI.');
        if (!self::on()) throw new ApiException('Analisis AI belum diaktifkan oleh admin.', 503);

        $kind = (string) ($in['kind'] ?? '');
        if (!in_array($kind, self::KINDS, true)) throw new ApiException('Jenis analisis tidak dikenal', 400);
        $maxItems = max(5, min(80, Settings::int('ai.max_items', 40)));
        $data = self::clean($in['data'] ?? null, 0, $maxItems);
        if (!is_array($data) || !$data) throw new ApiException('Data kosong', 400);
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($payload) > 60000) throw new ApiException('Data terlalu besar', 413);

        $model = trim((string) Settings::get('ai.model', 'deepseek-v4-flash')) ?: 'deepseek-v4-flash';
        [$sys, $usr] = AiPrompts::for($kind);
        $hash = hash('sha256', self::PROMPT_VERSION . '|' . $kind . '|' . $model . '|' . md5($sys . $usr) . '|' . $payload);

        // 1) cache (tidak memakai kuota)
        $ttl = max(0, Settings::int('ai.cache_hours', 24)) * 3600;
        if ($ttl > 0 && empty($in['force'])) {
            $cached = AiCache::query()->find($hash);
            if ($cached && $cached->created_at->getTimestamp() + $ttl > time()) {
                $cached->increment('hits');
                self::log($c, $kind, true, $model, 0, 0, 0, 'ok');
                return ['analysis' => json_decode($cached->result, true), 'cached' => true, 'generated_at' => $cached->created_at->getTimestamp(), 'ai' => self::info($c)];
            }
        }

        // 2) laju & kuota harian
        $perMin = Settings::int('ai.rate_per_min', 6);
        if ($perMin > 0 && AiLog::query()->where('customer_id', $c->id)->where('cache_hit', false)->where('created_at', '>=', now()->subMinute())->count() >= $perMin) {
            throw new ApiException('Terlalu cepat, tunggu sebentar lalu coba lagi.', 429);
        }
        $limit = self::limitFor($c);
        if ($limit !== null && self::usedToday($c) >= $limit) {
            throw new ApiException("Kuota analisis AI hari ini habis ($limit/hari). Reset pukul 00:00 WIB.", 429);
        }

        // 3) model utama, lalu cadangan
        $messages = [
            ['role' => 'system', 'content' => $sys],
            ['role' => 'user', 'content' => $usr . "\n\nData hasil scraping (JSON):\n<data>\n" . $payload . "\n</data>"],
        ];
        $models = [$model];
        $fb = trim((string) Settings::get('ai.fallback_model', ''));
        if ($fb !== '' && $fb !== $model) $models[] = $fb;

        $last = null;
        foreach ($models as $m) {
            $r = self::chat($messages, $m);
            $parsed = $r['ok'] ? self::parseJson($r['content']) : null;
            if ($r['ok'] && $parsed) {
                $analysis = self::normalize($parsed);
                $analysis['model'] = $r['model'];
                if ($analysis['ringkasan'] !== '' || $analysis['rekomendasi']) {
                    AiCache::query()->updateOrCreate(['hash' => $hash], ['kind' => $kind, 'model' => $m, 'result' => json_encode($analysis, JSON_UNESCAPED_UNICODE), 'hits' => 0, 'created_at' => now()]);
                    self::log($c, $kind, false, $m, $r['tin'], $r['tout'], $r['ms'], 'ok');
                    return ['analysis' => $analysis, 'cached' => false, 'generated_at' => time(), 'ai' => self::info($c)];
                }
            }
            $r['err'] = $r['err'] ?: 'Jawaban AI tidak bisa dibaca (bukan JSON)';
            self::log($c, $kind, false, $m, $r['tin'], $r['tout'], $r['ms'], 'error', $r['err']);
            $last = $r;
        }
        logger()->error('[spilltoko-ai] gagal: ' . ($last['err'] ?? '?'));
        // pesan teknis (saldo/key) hanya untuk admin (Pemakaian AI → log); pengguna dapat pesan umum
        throw new ApiException(in_array($last['status'] ?? 0, [401, 402, 403], true)
            ? 'Layanan AI sedang bermasalah, hubungi admin.' : 'AI gagal menjawab, coba lagi sebentar lagi.', 502);
    }
}
