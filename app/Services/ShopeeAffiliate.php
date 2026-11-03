<?php

namespace App\Services;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Klien Open API Affiliate Shopee (GraphQL). Kredensial diisi admin di Pengaturan → Affiliate Shopee.
 * Tanda tangan: Authorization: SHA256 Credential={AppId}, Timestamp={unix}, Signature=sha256(AppId + Timestamp + Payload + Secret)
 * dengan Payload = isi JSON body yang persis dikirim.
 */
class ShopeeAffiliate
{
    /** Kolom lengkap dulu; bila skema menolak salah satu kolom, jatuh ke set minimal. */
    private const FIELD_SETS = [
        'itemId productName shopId shopName priceMin priceMax sales commissionRate sellerCommissionRate shopeeCommissionRate commission ratingStar offerLink productLink',
        'itemId productName shopId shopName priceMin priceMax sales commissionRate offerLink',
    ];

    public static function configured(): bool
    {
        return Settings::bool('affiliate.enabled') && Settings::has('affiliate.app_id') && Settings::has('affiliate.secret');
    }

    /** @return array<string,mixed> body GraphQL (data / errors) */
    private static function post(string $query): array
    {
        $payload = json_encode(['query' => $query], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $appId = (string) Settings::get('affiliate.app_id', '');
        $ts = (string) time();
        $sig = hash('sha256', $appId . $ts . $payload . (string) Settings::get('affiliate.secret', ''));
        try {
            $r = Http::timeout(15)->withHeaders(['Authorization' => "SHA256 Credential=$appId, Timestamp=$ts, Signature=$sig", 'Content-Type' => 'application/json'])
                ->withBody($payload, 'application/json')->post((string) Settings::get('affiliate.endpoint'));
        } catch (\Throwable) {
            throw new ApiException('Server tidak bisa menghubungi Open API Affiliate Shopee', 502);
        }
        $j = $r->json();
        if (!is_array($j)) throw new ApiException('Respons Affiliate Shopee tidak valid (HTTP ' . $r->status() . ')', 502);
        return $j;
    }

    private static function errorText(array $j): ?string
    {
        if (empty($j['errors'])) return null;
        $e = $j['errors'][0] ?? [];
        $code = $e['extensions']['code'] ?? null;
        return trim(($code !== null ? "[$code] " : '') . ($e['message'] ?? 'galat tak dikenal'));
    }

    private static function isSchemaError(array $j): bool
    {
        $m = strtolower((string) (($j['errors'][0]['message'] ?? '')));
        return str_contains($m, 'cannot query field') || str_contains($m, 'unknown field') || str_contains($m, 'validation');
    }

    /** Ambil nodes productOfferV2 dengan argumen mentah (sudah divalidasi pemanggil). */
    private static function offers(string $args): array
    {
        $last = null;
        foreach (self::FIELD_SETS as $fields) {
            $j = self::post("query { productOfferV2($args) { nodes { $fields } } }");
            if (!empty($j['errors'])) {
                $last = $j;
                if (self::isSchemaError($j)) continue;
                throw new ApiException(self::errorText($j) ?? 'Galat Affiliate Shopee', 502);
            }
            return (array) ($j['data']['productOfferV2']['nodes'] ?? []);
        }
        throw new ApiException(self::errorText($last ?? []) ?? 'Galat Affiliate Shopee', 502);
    }

    /** Komisi dari Open API bisa berupa pecahan (0.07) atau string; kembalikan persen (7.0). */
    private static function pct(mixed $v): ?float
    {
        if ($v === null || $v === '' || !is_numeric($v)) return null;
        $f = (float) $v;
        return round($f <= 1 ? $f * 100 : $f, 2);
    }

    private static function num(mixed $v): ?float { return is_numeric($v) ? (float) $v : null; }

    /** Data komisi satu produk. Hasil di-cache. @return array<string,mixed>|null null = produk tidak ada di program affiliate */
    public static function product(string $shopId, string $itemId): ?array
    {
        if (!self::configured()) throw new ApiException('Data Affiliate belum diaktifkan admin', 503);
        if (!ctype_digit($shopId) || !ctype_digit($itemId)) throw new ApiException('Produk tidak valid', 400);
        $ttl = max(1, Settings::int('affiliate.cache_hours', 12)) * 3600;
        $hit = Cache::get("aff:$shopId.$itemId");
        if ($hit !== null) return $hit === false ? null : $hit;
        $nodes = self::offers("shopId: $shopId, itemId: $itemId, limit: 1");
        $n = $nodes[0] ?? null;
        $out = null;
        if ($n && (string) ($n['itemId'] ?? '') === $itemId) {
            $price = self::num($n['priceMin'] ?? null);
            $total = self::pct($n['commissionRate'] ?? null);
            $out = [
                'name' => $n['productName'] ?? null, 'shop' => $n['shopName'] ?? null,
                'price_min' => $price, 'price_max' => self::num($n['priceMax'] ?? null), 'sales' => self::num($n['sales'] ?? null),
                'rate_total' => $total, 'rate_seller' => self::pct($n['sellerCommissionRate'] ?? null), 'rate_shopee' => self::pct($n['shopeeCommissionRate'] ?? null),
                'commission' => self::num($n['commission'] ?? null) ?? ($price !== null && $total !== null ? round($price * $total / 100) : null),
                'rating' => self::num($n['ratingStar'] ?? null),
            ];
        }
        Cache::put("aff:$shopId.$itemId", $out ?? false, $ttl);
        return $out;
    }

    /** Tombol Tes di admin. @return array{0:bool,1:string} */
    public static function test(): array
    {
        if (!Settings::has('affiliate.app_id') || !Settings::has('affiliate.secret')) return [false, 'App ID dan Secret belum diisi.'];
        try {
            $nodes = self::offers('keyword: "baju", limit: 1');
        } catch (ApiException $e) {
            return [false, $e->getMessage()];
        }
        $n = $nodes[0] ?? null;
        if (!$n) return [true, 'Terhubung ✓ (kredensial diterima, tetapi pencarian contoh kosong).'];
        return [true, 'Terhubung ✓ Contoh data: ' . mb_substr(json_encode($n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 400)];
    }
}
