<?php

namespace App\Services;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;

class GoogleAuth
{
    /** Verifikasi id_token ke Google (aud, iss, exp, email_verified, nonce). Client ID dibaca dari Settings. */
    public static function verify(string $idToken, string $nonce): array
    {
        if ($idToken === '') throw new ApiException('id_token kosong', 400);
        $clientId = (string) Settings::get('google.client_id', '');
        if ($clientId === '') throw new ApiException('Login Google belum dikonfigurasi admin', 503);

        try {
            $r = Http::acceptJson()->timeout(15)->get(config('spill.google_tokeninfo_url'), ['id_token' => $idToken]);
        } catch (\Throwable) {
            throw new ApiException('Tidak bisa menghubungi Google, coba lagi', 502);
        }
        $j = $r->json();
        if (!$r->ok() || !is_array($j)) throw new ApiException('Token Google tidak valid', 401);
        if (($j['aud'] ?? '') !== $clientId) throw new ApiException('Client ID tidak cocok', 401);
        if (!in_array($j['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) throw new ApiException('Penerbit token tidak valid', 401);
        if ((int) ($j['exp'] ?? 0) < time()) throw new ApiException('Token Google kedaluwarsa', 401);
        $ev = $j['email_verified'] ?? false;
        if ($ev !== true && $ev !== 'true') throw new ApiException('Email Google belum terverifikasi', 401);
        if ($nonce === '' || ($j['nonce'] ?? '') !== $nonce) throw new ApiException('Nonce tidak cocok', 401);
        return $j;
    }
}
