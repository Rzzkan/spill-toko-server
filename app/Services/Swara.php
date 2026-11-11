<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\SwaraPairing;

/**
 * Swara: voice cloning (OmniVoice) jalan di Google Colab milik pengguna, antarmuka di temanseller.com.
 * Server TIDAK pernah menerima audio: browser memanggil Colab langsung (tunnel URL hanya ada di hash URL,
 * tidak pernah dikirim ke server kita). Server hanya mengurus lisensi dan menandatangani token pendek yang
 * diperiksa notebook (tanda tangan Ed25519; kunci publik dilayani lewat GET /api/swara/pubkey, aman dibuka
 * karena kunci publik bukan rahasia).
 *
 * Satu sesi aktif per lisensi: pairing (sid) baru SELALU menimpa yang lama (lihat pair()). Tab/notebook lama
 * otomatis ditolak saat token-nya diperpanjang karena sid yang dikirim sudah tidak cocok dengan pairing terkini
 * (lihat issueToken()) — bukan di-pair ulang, karena refresh token tidak memanggil pair().
 */
class Swara
{
    public const APP = 'swara';

    /** Umur token (detik). Notebook menolak token yang sudah lewat exp; browser memperpanjang berkala. */
    public const TOKEN_TTL = 900;

    private static function secretKey(): string
    {
        $b64 = Settings::raw('security.swara_sign_key');
        $sk = $b64 ? base64_decode($b64, true) : false;
        if ($sk === false || strlen($sk) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            $kp = sodium_crypto_sign_keypair();
            $sk = sodium_crypto_sign_secretkey($kp);
            Settings::set('security.swara_sign_key', base64_encode($sk), true);
        }
        return $sk;
    }

    /** Kunci publik (base64) untuk diperiksa notebook Colab (lihat GET /api/swara/pubkey). */
    public static function publicKey(): string
    {
        return base64_encode(sodium_crypto_sign_publickey_from_secretkey(self::secretKey()));
    }

    public static function regenerateKey(): string
    {
        Settings::forget('security.swara_sign_key');
        return self::publicKey();
    }

    private static function sidOk(string $sid): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{6,64}$/', $sid);
    }

    /** Dipanggil saat halaman /akun/swara memuat sid baru dari hash URL (notebook baru dijalankan). Selalu menang. */
    public static function pair(Customer $c, string $sid): void
    {
        if (!self::sidOk($sid)) throw new ApiException('Kode sesi dari notebook tidak valid', 422);
        $c->requireAccess(self::APP, 'Swara belum dibeli atau lisensi sudah berakhir');
        SwaraPairing::query()->updateOrCreate(['customer_id' => $c->id], ['sid' => $sid, 'paired_at' => now()]);
    }

    /**
     * Dipanggil berkala (±10 menit) oleh tab yang sedang aktif untuk memperpanjang token.
     * Gagal (409) bila sid pemanggil bukan lagi sid yang sedang di-pair (sesi sudah digantikan sesi lain).
     */
    public static function issueToken(Customer $c, string $sid): array
    {
        if (!self::sidOk($sid)) throw new ApiException('Kode sesi tidak valid', 422);
        $c->requireAccess(self::APP, 'Swara belum dibeli atau lisensi sudah berakhir');
        $p = SwaraPairing::query()->where('customer_id', $c->id)->first();
        if (!$p || $p->sid !== $sid) {
            throw new ApiException('Sesi ini sudah digantikan sesi lain (notebook baru dijalankan). Buka ulang link dari notebook yang masih aktif.', 409);
        }
        $iat = time();
        $exp = $iat + self::TOKEN_TTL;
        $payload = json_encode(['uid' => $c->id, 'sid' => $sid, 'iat' => $iat, 'exp' => $exp], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        try {
            $sig = sodium_crypto_sign_detached($payload, self::secretKey());
        } catch (\Throwable) {
            throw new ApiException('Kunci lisensi Swara bermasalah', 500);
        }
        return ['token' => base64_encode($payload) . '.' . base64_encode($sig), 'exp' => $exp];
    }
}
