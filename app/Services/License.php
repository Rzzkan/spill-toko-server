<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\App as AppModel;
use App\Models\Customer;

/**
 * Lisensi bertanda tangan Ed25519: extension memverifikasi dengan public key sehingga data di storage
 * tidak bisa dipalsukan. Kunci dibuat otomatis saat pertama dipakai dan disimpan terenkripsi di Settings.
 * JANGAN diganti setelah extension dirilis (public key di extension akan tidak cocok).
 */
class License
{
    private static function secretKey(): string
    {
        $b64 = Settings::raw('security.license_sign_key');
        $sk = $b64 ? base64_decode($b64, true) : false;
        if ($sk === false || strlen($sk) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            $kp = sodium_crypto_sign_keypair();
            $sk = sodium_crypto_sign_secretkey($kp);
            Settings::set('security.license_sign_key', base64_encode($sk), true);
        }
        return $sk;
    }

    /** Public key (base64) untuk src/config.js di extension. */
    public static function publicKey(): string
    {
        return base64_encode(sodium_crypto_sign_publickey_from_secretkey(self::secretKey()));
    }

    public static function regenerate(): string
    {
        Settings::forget('security.license_sign_key');
        return self::publicKey();
    }

    public static function make(Customer $c, string $app = AppModel::DEFAULT): array
    {
        $a = $c->access($app);
        $sub = $c->subscription($app);
        $iat = time();
        $exp = $iat + max(1, Settings::int('license_ttl_hours', 72)) * 3600;
        if ($a['access'] && $a['until'] !== null) $exp = min($exp, $a['until']);
        $payload = json_encode([
            'uid' => $c->id, 'app' => $app, 'email' => $c->email, 'name' => $c->name, 'picture' => $c->picture,
            'plan' => $a['plan'], 'access' => $a['access'], 'until' => $a['until'],
            'trial_end' => $sub?->trial_ends_at?->getTimestamp(), 'paid_until' => $sub?->paid_until?->getTimestamp(),
            'iat' => $iat, 'exp' => $exp,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        try {
            $sig = sodium_crypto_sign_detached($payload, self::secretKey());
        } catch (\Throwable $e) {
            throw new ApiException('Kunci lisensi server bermasalah', 500);
        }
        return ['payload' => $payload, 'sig' => base64_encode($sig)];
    }
}
