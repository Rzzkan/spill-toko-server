<?php

namespace App\Services;

/** Token sesi HS256 untuk extension (60 hari). Secret dibuat otomatis & disimpan terenkripsi di Settings. */
class Jwt
{
    private static function secret(): string
    {
        $s = Settings::raw('security.jwt_secret');
        if (!$s) { $s = bin2hex(random_bytes(32)); Settings::set('security.jwt_secret', $s, true); }
        return $s;
    }

    private static function b64u(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
    private static function b64uDec(string $s): string { return (string) base64_decode(strtr($s, '-_', '+/')); }

    public static function sign(array $claims): string
    {
        $h = self::b64u(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $p = self::b64u(json_encode($claims));
        return "$h.$p." . self::b64u(hash_hmac('sha256', "$h.$p", self::secret(), true));
    }

    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        $calc = self::b64u(hash_hmac('sha256', "$parts[0].$parts[1]", self::secret(), true));
        if (!hash_equals($calc, $parts[2])) return null;
        $claims = json_decode(self::b64uDec($parts[1]), true);
        if (!is_array($claims) || ($claims['exp'] ?? 0) < time()) return null;
        return $claims;
    }
}
