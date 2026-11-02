<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/ai.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

$route = trim((string) ($_GET['r'] ?? ''), '/');
$key = ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' ' . $route;

try {
    switch ($key) {
        case 'GET plans':
            out(['plans' => plans_public(), 'trial_days' => trial_days()]);

        case 'POST auth': {
            $in = input();
            $g = verify_google_token((string) ($in['id_token'] ?? ''), (string) ($in['nonce'] ?? ''));
            $pdo = db();
            $st = $pdo->prepare('SELECT * FROM users WHERE google_sub = ?');
            $st->execute([$g['sub']]);
            $u = $st->fetch();
            $now = time();
            if (!$u) {
                $pdo->prepare('INSERT INTO users (google_sub, email, name, picture, trial_ends_at, lifetime, created_at, last_login_at) VALUES (?,?,?,?,?,0,?,?)')
                    ->execute([$g['sub'], $g['email'], $g['name'] ?? null, $g['picture'] ?? null,
                        utc($now + trial_days() * 86400), utc($now), utc($now)]);
                $st = $pdo->prepare('SELECT * FROM users WHERE google_sub = ?');
                $st->execute([$g['sub']]);
                $u = $st->fetch();
            } else {
                $pdo->prepare('UPDATE users SET email = ?, name = ?, picture = ?, last_login_at = ? WHERE id = ?')
                    ->execute([$g['email'], $g['name'] ?? $u['name'], $g['picture'] ?? $u['picture'], utc($now), $u['id']]);
                $u['email'] = $g['email'];
                $u['name'] = $g['name'] ?? $u['name'];
                $u['picture'] = $g['picture'] ?? $u['picture'];
            }
            $token = jwt_sign(['sub' => (int) $u['id'], 'iat' => $now, 'exp' => $now + 60 * 86400]);
            out(['token' => $token, 'license' => make_license($u), 'plans' => plans_public(), 'ai' => ai_info($u)]);
        }

        case 'GET me': {
            $u = current_user();
            out(['license' => make_license($u), 'plans' => plans_public(), 'ai' => ai_info($u)]);
        }

        case 'POST checkout': {
            $u = current_user();
            $plan = (string) (input()['plan'] ?? '');
            $plans = plans();
            if (!isset($plans[$plan])) fail('Paket tidak dikenal', 400);
            if ($plan === 'lifetime' && (int) $u['lifetime'] === 1) fail('Akun ini sudah Lifetime', 409);
            $p = $plans[$plan];
            $orderId = 'ST-' . time() . '-' . bin2hex(random_bytes(4));

            $pdo = db();
            $pdo->prepare('INSERT INTO orders (order_id, user_id, plan, amount, status, created_at) VALUES (?,?,?,?,?,?)')
                ->execute([$orderId, $u['id'], $plan, $p['price'], 'pending', utc()]);

            $urls = midtrans_urls();
            [$st, $r] = http_json('POST', $urls['snap'], midtrans_auth(), [
                'transaction_details' => ['order_id' => $orderId, 'gross_amount' => (int) $p['price']],
                'item_details' => [['id' => $plan, 'price' => (int) $p['price'], 'quantity' => 1, 'name' => 'Spill Toko - ' . $p['label']]],
                'customer_details' => ['email' => $u['email'], 'first_name' => (string) ($u['name'] ?: 'Pelanggan')],
                'callbacks' => ['finish' => cfg('app_url') . '/thanks.php?order=' . $orderId],
                'expiry' => ['unit' => 'hours', 'duration' => 24],
            ]);
            if (!in_array($st, [200, 201], true) || empty($r['redirect_url'])) {
                $pdo->prepare('UPDATE orders SET status = ? WHERE order_id = ?')->execute(['failed', $orderId]);
                error_log('Midtrans snap error: ' . json_encode($r));
                fail('Gagal membuat pembayaran, coba lagi', 502);
            }
            $pdo->prepare('UPDATE orders SET snap_token = ? WHERE order_id = ?')->execute([$r['token'] ?? null, $orderId]);
            out(['order_id' => $orderId, 'redirect_url' => $r['redirect_url']]);
        }

        case 'POST ai/analyze':
            ai_handle(current_user(), input());

        case 'GET orders': {
            $u = current_user();
            $st = db()->prepare('SELECT order_id, plan, amount, status, created_at, paid_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 20');
            $st->execute([$u['id']]);
            out(['orders' => $st->fetchAll()]);
        }

        case 'POST midtrans/notify': {
            $n = input();
            $orderId = (string) ($n['order_id'] ?? '');
            $serverKey = (string) cfg('midtrans')['server_key'];
            $expect = hash('sha512', $orderId . ($n['status_code'] ?? '') . ($n['gross_amount'] ?? '') . $serverKey);
            if (!hash_equals($expect, (string) ($n['signature_key'] ?? ''))) fail('invalid signature', 403);

            $st = db()->prepare('SELECT * FROM orders WHERE order_id = ?');
            $st->execute([$orderId]);
            $order = $st->fetch();
            if (!$order) out(['ok' => true, 'note' => 'unknown order']);

            // verifikasi ulang ke server Midtrans (jangan percaya payload mentah)
            $urls = midtrans_urls();
            [$hs, $s] = http_json('GET', $urls['api'] . '/v2/' . rawurlencode($orderId) . '/status', midtrans_auth());
            if ($hs !== 200 || !$s || ($s['order_id'] ?? '') !== $orderId || (string) ($s['status_code'] ?? '') === '404') fail('status check failed', 502);
            if ((int) round((float) ($s['gross_amount'] ?? 0)) !== (int) $order['amount']) { error_log("amount mismatch $orderId"); fail('amount mismatch', 400); }

            $ts = (string) ($s['transaction_status'] ?? '');
            $fraud = (string) ($s['fraud_status'] ?? 'accept');
            if ($ts === 'settlement' || ($ts === 'capture' && $fraud === 'accept')) {
                mark_paid($orderId, $s['payment_type'] ?? null, json_encode($s));
            } elseif (in_array($ts, ['expire', 'cancel', 'deny'], true)) {
                $map = ['expire' => 'expired', 'cancel' => 'cancelled', 'deny' => 'denied'];
                db()->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND status <> 'paid'")->execute([$map[$ts], $orderId]);
            }
            out(['ok' => true]);
        }

        default:
            fail('Endpoint tidak ditemukan', 404);
    }
} catch (Throwable $e) {
    error_log('[spilltoko] ' . $e);
    fail('Terjadi kesalahan server', 500);
}
