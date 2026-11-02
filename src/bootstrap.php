<?php
declare(strict_types=1);

// ---------- config & database ----------
function cfg(?string $key = null, $default = null)
{
    static $c = null;
    if ($c === null) {
        $path = getenv('SPILL_CONFIG') ?: __DIR__ . '/../config.php';
        if (!is_file($path)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo '{"error":"config.php belum dibuat"}';
            exit;
        }
        $c = require $path;
    }
    return $key === null ? $c : ($c[$key] ?? $default);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $d = cfg('db');
        $pdo = new PDO($d['dsn'], $d['user'] ?? null, $d['pass'] ?? null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// ---------- util ----------
function utc(?int $ts = null): string { return gmdate('Y-m-d H:i:s', $ts ?? time()); }
function ts(?string $s): int { return $s ? (int) strtotime($s . ' UTC') : 0; }

function out($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $msg, int $code = 400): never { out(['error' => $msg], $code); }

function input(): array
{
    $j = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($j) ? $j : [];
}

function b64u(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64u_dec(string $s): string { return (string) base64_decode(strtr($s, '-_', '+/')); }

function http_json(string $method, string $url, array $headers = [], ?array $body = null, int $timeout = 15): array
{
    $ch = curl_init($url);
    $h = array_merge(['Accept: application/json'], $headers);
    if ($body !== null) {
        $h[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $h,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    return [$status, is_array($json) ? $json : null];
}

// ---------- token sesi (HS256 JWT) ----------
function jwt_sign(array $claims): string
{
    $h = b64u(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $p = b64u(json_encode($claims));
    return "$h.$p." . b64u(hash_hmac('sha256', "$h.$p", (string) cfg('jwt_secret'), true));
}

function jwt_verify(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    $calc = b64u(hash_hmac('sha256', "$parts[0].$parts[1]", (string) cfg('jwt_secret'), true));
    if (!hash_equals($calc, $parts[2])) return null;
    $claims = json_decode(b64u_dec($parts[1]), true);
    if (!is_array($claims) || ($claims['exp'] ?? 0) < time()) return null;
    return $claims;
}

function current_user(): array
{
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $hdr, $m)) fail('Belum login', 401);
    $claims = jwt_verify(trim($m[1]));
    if (!$claims) fail('Sesi tidak valid, silakan login ulang', 401);
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([(int) $claims['sub']]);
    $u = $st->fetch();
    if (!$u) fail('Akun tidak ditemukan', 401);
    return $u;
}

// ---------- Google ----------
function verify_google_token(string $idToken, string $nonce): array
{
    if ($idToken === '') fail('id_token kosong', 400);
    $url = cfg('google_tokeninfo_url', 'https://oauth2.googleapis.com/tokeninfo') . '?id_token=' . urlencode($idToken);
    [$st, $j] = http_json('GET', $url);
    if ($st !== 200 || !$j) fail('Token Google tidak valid', 401);
    if (($j['aud'] ?? '') !== cfg('google_client_id')) fail('Client ID tidak cocok', 401);
    if (!in_array($j['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) fail('Penerbit token tidak valid', 401);
    if ((int) ($j['exp'] ?? 0) < time()) fail('Token Google kedaluwarsa', 401);
    $ev = $j['email_verified'] ?? false;
    if ($ev !== true && $ev !== 'true') fail('Email Google belum terverifikasi', 401);
    if ($nonce === '' || ($j['nonce'] ?? '') !== $nonce) fail('Nonce tidak cocok', 401);
    return $j;
}

// ---------- akses & lisensi ----------
function access_info(array $u): array
{
    $now = time();
    $paid = ts($u['paid_until'] ?? null);
    $trial = ts($u['trial_ends_at'] ?? null);
    if ((int) $u['lifetime'] === 1) return ['plan' => 'lifetime', 'access' => true, 'until' => null];
    if ($paid > $now) return ['plan' => 'monthly', 'access' => true, 'until' => $paid];
    if ($trial > $now) return ['plan' => 'trial', 'access' => true, 'until' => $trial];
    return ['plan' => 'expired', 'access' => false, 'until' => max($paid, $trial)];
}

// Lisensi bertanda tangan Ed25519: extension memverifikasi dengan public key sehingga data di storage tidak bisa dipalsukan.
function make_license(array $u): array
{
    $a = access_info($u);
    $iat = time();
    $exp = $iat + (int) cfg('license_ttl_hours', 72) * 3600;
    if ($a['access'] && $a['until'] !== null) $exp = min($exp, $a['until']);
    $payload = json_encode([
        'uid' => (int) $u['id'], 'email' => $u['email'], 'name' => $u['name'], 'picture' => $u['picture'],
        'plan' => $a['plan'], 'access' => $a['access'], 'until' => $a['until'],
        'trial_end' => ts($u['trial_ends_at']), 'paid_until' => ts($u['paid_until'] ?? null) ?: null,
        'iat' => $iat, 'exp' => $exp,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $sk = base64_decode((string) cfg('license_sign_key'), true);
    if ($sk === false || strlen($sk) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) fail('license_sign_key belum diatur (jalankan tools/keygen.php)', 500);
    return ['payload' => $payload, 'sig' => base64_encode(sodium_crypto_sign_detached($payload, $sk))];
}

function plans(): array
{
    $pl = cfg('plans');
    foreach ($pl as $k => &$p) {
        $pr = setting("plan.$k.price");
        if ($pr !== null && $pr !== '' && (int) $pr > 0) $p['price'] = (int) $pr;
        if ($p['days'] !== null) {
            $d = setting("plan.$k.days");
            if ($d !== null && $d !== '' && (int) $d > 0) $p['days'] = (int) $d;
        }
    }
    return $pl;
}

function trial_days(): int
{
    $t = setting('trial_days');
    return ($t !== null && $t !== '' && (int) $t >= 0) ? (int) $t : (int) cfg('trial_days', 7);
}

function plans_public(): array
{
    $o = [];
    foreach (plans() as $k => $p) $o[$k] = ['label' => $p['label'], 'price' => $p['price'], 'days' => $p['days']];
    return $o;
}

// ---------- Midtrans ----------
function midtrans_urls(): array
{
    $prod = (bool) (cfg('midtrans')['is_production'] ?? false);
    return [
        'snap' => cfg('midtrans_snap_url', $prod ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions'),
        'api' => cfg('midtrans_api_url', $prod ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com'),
    ];
}

function midtrans_auth(): array
{
    return ['Authorization: Basic ' . base64_encode((string) cfg('midtrans')['server_key'] . ':')];
}

function mark_paid(string $orderId, ?string $paymentType, string $raw): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM orders WHERE order_id = ? FOR UPDATE');
        $st->execute([$orderId]);
        $o = $st->fetch();
        if (!$o || $o['status'] === 'paid') { $pdo->commit(); return; }   // idempoten

        $pdo->prepare('UPDATE orders SET status = ?, paid_at = ?, payment_type = ?, raw_notification = ? WHERE id = ?')
            ->execute(['paid', utc(), $paymentType, $raw, $o['id']]);

        $us = $pdo->prepare('SELECT * FROM users WHERE id = ? FOR UPDATE');
        $us->execute([$o['user_id']]);
        $u = $us->fetch();
        if ($o['plan'] === 'lifetime') {
            $pdo->prepare('UPDATE users SET lifetime = 1 WHERE id = ?')->execute([$u['id']]);
        } else {
            $days = (int) (plans()[$o['plan']]['days'] ?? 30);
            $base = max(time(), ts($u['paid_until'] ?? null));
            $pdo->prepare('UPDATE users SET paid_until = ? WHERE id = ?')->execute([utc($base + $days * 86400), $u['id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}


// ---------- pengaturan (tabel settings, diubah dari halaman admin) ----------
function settings_all(bool $reload = false): array
{
    static $c = null;
    if ($c === null || $reload) {
        $c = [];
        try {
            foreach (db()->query('SELECT k, v, is_secret FROM settings') as $r) {
                $c[$r['k']] = (int) $r['is_secret'] === 1 ? secret_dec((string) $r['v']) : $r['v'];
            }
        } catch (Throwable $e) { /* tabel belum dibuat: pakai default */ }
    }
    return $c;
}

function setting(string $k, $default = null)
{
    $a = settings_all();
    return array_key_exists($k, $a) && $a[$k] !== null ? $a[$k] : $default;
}

function setting_set(string $k, ?string $v, bool $secret = false): void
{
    $val = ($secret && $v !== null && $v !== '') ? secret_enc($v) : $v;
    db()->prepare('INSERT INTO settings (k, v, is_secret, updated_at) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE v = VALUES(v), is_secret = VALUES(is_secret), updated_at = VALUES(updated_at)')
        ->execute([$k, $val, $secret ? 1 : 0, utc()]);
    settings_all(true);
}

function secret_key(): string { return sodium_crypto_generichash('spilltoko-settings|' . (string) cfg('jwt_secret'), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES); }

function secret_enc(string $plain): string
{
    $n = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return 'enc:' . base64_encode($n . sodium_crypto_secretbox($plain, $n, secret_key()));
}

function secret_dec(string $s): string
{
    if (strpos($s, 'enc:') !== 0) return $s;
    $raw = base64_decode(substr($s, 4), true);
    if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) return '';
    $p = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), secret_key());
    return $p === false ? '' : $p;
}
