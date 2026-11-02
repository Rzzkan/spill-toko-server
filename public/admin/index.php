<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/ai.php';

// ---- Basic Auth ----
// Sebagian hosting (CGI/FastCGI Apache) tidak mengisi PHP_AUTH_*; ambil dari header Authorization.
if (empty($_SERVER['PHP_AUTH_USER'])) {
    $ah = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (stripos($ah, 'basic ') === 0) {
        $dec = explode(':', (string) base64_decode(substr($ah, 6)), 2);
        if (count($dec) === 2) { $_SERVER['PHP_AUTH_USER'] = $dec[0]; $_SERVER['PHP_AUTH_PW'] = $dec[1]; }
    }
}
$adm = cfg('admin', []);
$ok = !empty($adm['pass_hash'])
    && hash_equals((string) ($adm['user'] ?? 'admin'), (string) ($_SERVER['PHP_AUTH_USER'] ?? ''))
    && password_verify((string) ($_SERVER['PHP_AUTH_PW'] ?? ''), (string) $adm['pass_hash']);
if (!$ok) {
    header('WWW-Authenticate: Basic realm="Spill Toko Admin"');
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$pdo = db();
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$rp = fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
$tab = in_array($_GET['tab'] ?? '', ['users', 'orders', 'settings', 'ai'], true) ? $_GET['tab'] : 'users';
$flash = '';
$flashBad = false;

// ---- daftar pengaturan yang bisa diubah ----
// [kunci => [label, tipe, default, bantuan]]
$SET_SUB = [
    'trial_days'            => ['Lama trial (hari)', 'number', (string) cfg('trial_days', 7), 'Berlaku untuk akun BARU.'],
    'plan.monthly.price'    => ['Harga Bulanan (Rp)', 'number', (string) (cfg('plans')['monthly']['price'] ?? 25000), ''],
    'plan.monthly.days'     => ['Masa aktif Bulanan (hari)', 'number', (string) (cfg('plans')['monthly']['days'] ?? 30), ''],
    'plan.lifetime.price'   => ['Harga Lifetime (Rp)', 'number', (string) (cfg('plans')['lifetime']['price'] ?? 200000), ''],
];
$SET_AI = [
    'ai.enabled'        => ['Aktifkan analisis AI', 'bool', '1', 'Matikan untuk menyembunyikan fitur AI di semua extension.'],
    'ai.auto'           => ['Jalankan otomatis', 'bool', '1', 'Extension langsung meminta analisis begitu data selesai di-scrape (hasil cache tidak memakai kuota).'],
    'ai.base_url'       => ['Base URL', 'text', 'https://kenari.id/v1', 'Endpoint kompatibel OpenAI.'],
    'ai.api_key'        => ['API key kenari.id', 'secret', '', 'Format kn-…  Disimpan terenkripsi. Kosongkan kolom untuk mempertahankan key lama.'],
    'ai.model'          => ['Model utama', 'model', 'deepseek-v4-flash', 'ID model kenari. Klik "Muat daftar model" untuk memilih.'],
    'ai.fallback_model' => ['Model cadangan', 'model', '', 'Dipakai bila model utama gagal (opsional).'],
    'ai.temperature'    => ['Temperature', 'text', '0.3', '0–1. Rendah = lebih konsisten.'],
    'ai.max_tokens'     => ['Maks token jawaban', 'number', '2200', ''],
    'ai.json_mode'      => ['Paksa mode JSON', 'bool', '1', 'Kirim response_format=json_object. Otomatis dicoba ulang tanpa itu bila model menolak.'],
    'ai.timeout'        => ['Timeout (detik)', 'number', '70', ''],
    'ai.cache_hours'    => ['Cache hasil (jam)', 'number', '24', 'Data yang sama persis tidak dianalisis ulang. 0 = matikan cache.'],
    'ai.max_items'      => ['Maks produk dikirim ke AI', 'number', '40', 'Membatasi ukuran prompt (5–80).'],
    'ai.quota.trial'    => ['Kuota/hari – Trial', 'number', '20', '0 = tanpa batas. Hanya analisis baru yang dihitung (bukan cache). Reset 00:00 WIB.'],
    'ai.quota.monthly'  => ['Kuota/hari – Bulanan', 'number', '100', ''],
    'ai.quota.lifetime' => ['Kuota/hari – Lifetime', 'number', '100', ''],
    'ai.rate_per_min'   => ['Maks analisis/menit per pengguna', 'number', '6', '0 = tanpa batas.'],
];
$SET_PROMPT = [
    'ai.prompt.system'  => 'Prompt sistem (peran & aturan)',
    'ai.prompt.product' => 'Instruksi: halaman produk',
    'ai.prompt.shop'    => 'Instruksi: halaman toko',
    'ai.prompt.search'  => 'Instruksi: halaman pencarian',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) { http_response_code(403); exit('CSRF'); }
    $act = (string) ($_POST['act'] ?? '');

    if (in_array($act, ['days', 'lifetime', 'revoke'], true)) {
        $uid = (int) ($_POST['uid'] ?? 0);
        $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$uid]);
        $u = $st->fetch();
        if ($u) {
            if ($act === 'days') {
                $days = max(1, min(3650, (int) ($_POST['days'] ?? 30)));
                $base = max(time(), ts($u['paid_until'] ?? null));
                $pdo->prepare('UPDATE users SET paid_until = ? WHERE id = ?')->execute([utc($base + $days * 86400), $uid]);
                $note = "+$days hari";
            } elseif ($act === 'lifetime') {
                $pdo->prepare('UPDATE users SET lifetime = 1 WHERE id = ?')->execute([$uid]);
                $note = 'lifetime';
            } else {
                $pdo->prepare('UPDATE users SET lifetime = 0, paid_until = NULL, trial_ends_at = ? WHERE id = ?')->execute([utc(), $uid]);
                $note = 'dicabut';
            }
            $pdo->prepare('INSERT INTO admin_log (user_id, action, note, created_at) VALUES (?,?,?,?)')->execute([$uid, $act, $note, utc()]);
            $flash = 'Tersimpan: ' . $h($u['email']) . ' (' . $h($note) . ')';
        }
    } elseif ($act === 'save_sub' || $act === 'save_ai') {
        $tab = $act === 'save_sub' ? 'settings' : 'settings';
        $defs = $act === 'save_sub' ? $SET_SUB : $SET_AI;
        foreach ($defs as $k => [$label, $type]) {
            $pk = str_replace('.', '_', $k);
            if ($type === 'secret') {
                $v = trim((string) ($_POST[$pk] ?? ''));
                if ($v !== '') setting_set($k, $v, true);
                elseif (!empty($_POST[$pk . '_clear'])) setting_set($k, '', true);
                continue;
            }
            $v = $type === 'bool' ? (isset($_POST[$pk]) ? '1' : '0') : trim((string) ($_POST[$pk] ?? ''));
            if ($type === 'number' && $v !== '' && !ctype_digit($v)) { $flash = 'Nilai "' . $h($label) . '" harus berupa angka bulat.'; $flashBad = true; continue; }
            if ($k === 'ai.base_url' && $v !== '' && !preg_match('#^https://#i', $v)) { $flash = 'Base URL harus diawali https://'; $flashBad = true; continue; }
            if ($k === 'ai.temperature' && ($v === '' || !is_numeric($v) || (float) $v < 0 || (float) $v > 2)) { $flash = 'Temperature harus 0–2.'; $flashBad = true; continue; }
            setting_set($k, $v);
        }
        if ($act === 'save_ai') {
            foreach ($SET_PROMPT as $k => $label) setting_set($k, trim((string) ($_POST[str_replace('.', '_', $k)] ?? '')));
        }
        if (!$flashBad) $flash = 'Pengaturan disimpan.';
    } elseif ($act === 'ai_models') {
        $tab = 'settings';
        $base = rtrim((string) setting('ai.base_url', 'https://kenari.id/v1'), '/');
        [$st, $j] = http_json('GET', $base . '/models', [], null, 20);
        $ids = [];
        foreach (($j['data'] ?? []) as $m) if (!empty($m['id'])) $ids[] = (string) $m['id'];
        sort($ids);
        if ($st === 200 && $ids) { setting_set('ai.models_cache', json_encode($ids)); $flash = 'Memuat ' . count($ids) . ' model.'; }
        else { $flash = 'Gagal memuat daftar model (HTTP ' . (int) $st . ').'; $flashBad = true; }
    } elseif ($act === 'ai_test') {
        $tab = 'settings';
        if (ai_key() === '') { $flash = 'API key belum diisi.'; $flashBad = true; }
        else {
            $m = trim((string) setting('ai.model', 'deepseek-v4-flash'));
            $r = ai_chat([['role' => 'user', 'content' => 'Balas dengan satu kata saja: OK']], $m);
            if ($r['ok']) $flash = 'Terhubung ✓ model ' . $h($r['model']) . ' · ' . (int) $r['ms'] . ' ms · jawaban: ' . $h(mb_substr(trim($r['content']), 0, 60));
            else { $flash = 'Gagal: ' . $h($r['err']); $flashBad = true; }
        }
    } elseif ($act === 'ai_clear_cache') {
        $tab = 'ai';
        $n = $pdo->exec('DELETE FROM ai_cache');
        $flash = "Cache AI dikosongkan ($n entri).";
    }
}

$S = fn(string $k, string $d = '') => (string) setting($k, $d);
$q = trim((string) ($_GET['q'] ?? ''));
$stat = in_array($_GET['st'] ?? '', ['trial', 'monthly', 'lifetime', 'expired'], true) ? $_GET['st'] : '';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 50;
$nowS = utc();

// ---- ringkasan (kartu) ----
$cnt = $pdo->prepare("SELECT
  SUM(lifetime = 1) AS lt,
  SUM(lifetime = 0 AND paid_until > ?) AS mo,
  SUM(lifetime = 0 AND (paid_until IS NULL OR paid_until <= ?) AND trial_ends_at > ?) AS tr,
  SUM(lifetime = 0 AND (paid_until IS NULL OR paid_until <= ?) AND trial_ends_at <= ?) AS ex,
  COUNT(*) AS n FROM users");
$cnt->execute([$nowS, $nowS, $nowS, $nowS, $nowS]);
$C = $cnt->fetch();
$rev = $pdo->query("SELECT COALESCE(SUM(amount),0) AS s, COALESCE(SUM(paid_at >= DATE_FORMAT(UTC_TIMESTAMP(), '%Y-%m-01') ) * 0, 0) AS z FROM orders WHERE status='paid'")->fetch();
$revM = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='paid' AND paid_at >= DATE_FORMAT(UTC_TIMESTAMP(), '%Y-%m-01')")->fetchColumn();
$aiToday = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(cache_hit=1),0) AS c, COALESCE(SUM(status='error'),0) AS e, COALESCE(SUM(tokens_in),0) AS ti, COALESCE(SUM(tokens_out),0) AS tu FROM ai_log WHERE created_at >= ?");
$aiToday->execute([utc(ai_day_start())]);
$AT = $aiToday->fetch();

function admin_url(array $over = []): string
{
    return '?' . http_build_query(array_filter(array_merge($_GET, $over), fn($v) => $v !== '' && $v !== null));
}
$csrf = '<input type="hidden" name="csrf" value="' . $h($_SESSION['csrf']) . '">';
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Spill Toko</title>
<style>
body{font:14px system-ui,sans-serif;margin:0;background:#f5f5f7;color:#222}main{max-width:1180px;margin:0 auto;padding:20px}
h1{font-size:20px;margin:0 0 12px}h2{font-size:16px;margin:26px 0 10px}h3{font-size:14px;margin:18px 0 8px}
.cards{display:flex;gap:10px;flex-wrap:wrap}.card{background:#fff;border-radius:8px;padding:10px 14px;box-shadow:0 1px 2px rgba(0,0,0,.08);min-width:120px}.card b{display:block;font-size:19px}.card small{color:#666}
nav{display:flex;gap:4px;margin:16px 0;border-bottom:2px solid #e3e3e8}nav a{padding:8px 14px;text-decoration:none;color:#555;border-radius:6px 6px 0 0}nav a.on{background:#ee4d2d;color:#fff}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden}th,td{padding:8px 10px;border-bottom:1px solid #eee;text-align:left;font-size:13px;vertical-align:top}th{background:#fafafa}
form.inline{display:inline-flex;gap:4px;align-items:center}input[type=number]{width:70px}button{cursor:pointer}
.tag{padding:2px 8px;border-radius:99px;font-size:12px;background:#eee}.ok{background:#d9f7e4}.bad{background:#fde2e2}.warn{background:#fff3cd}
.flash{background:#e8f0fe;padding:10px;border-radius:6px;margin:10px 0}.flash.err{background:#fde2e2}
.box{background:#fff;border-radius:8px;padding:14px 18px;box-shadow:0 1px 2px rgba(0,0,0,.08);margin-bottom:14px}
.grid{display:grid;grid-template-columns:240px 1fr;gap:8px 14px;align-items:start}.grid label{padding-top:6px;font-weight:600}.grid small{display:block;color:#777;font-weight:400;margin-top:2px}
input[type=text],input[type=password],input[type=number].w,select,textarea{width:100%;max-width:560px;padding:6px 8px;border:1px solid #ccc;border-radius:6px;font:inherit;box-sizing:border-box}
textarea{min-height:110px;font:12px/1.4 ui-monospace,monospace}
.btn{background:#ee4d2d;color:#fff;border:0;padding:8px 16px;border-radius:6px;font-weight:600}.btn.sec{background:#555}
.filters a{display:inline-block;margin-right:6px;padding:3px 10px;border-radius:99px;background:#fff;text-decoration:none;color:#333;border:1px solid #ddd}.filters a.on{background:#222;color:#fff}
pre{white-space:pre-wrap;background:#f1f1f4;padding:10px;border-radius:6px;font-size:12px}
@media(max-width:720px){.grid{grid-template-columns:1fr}}
</style></head><body><main>
<h1>Admin Spill Toko</h1>
<?php if ($flash): ?><div class="flash<?= $flashBad ? ' err' : '' ?>"><?= $flash ?></div><?php endif; ?>
<div class="cards">
  <div class="card"><small>Total akun</small><b><?= (int) $C['n'] ?></b></div>
  <div class="card"><small>Trial aktif</small><b><?= (int) $C['tr'] ?></b></div>
  <div class="card"><small>Bulanan aktif</small><b><?= (int) $C['mo'] ?></b></div>
  <div class="card"><small>Lifetime</small><b><?= (int) $C['lt'] ?></b></div>
  <div class="card"><small>Kedaluwarsa</small><b><?= (int) $C['ex'] ?></b></div>
  <div class="card"><small>Pendapatan bulan ini</small><b><?= $rp($revM) ?></b></div>
  <div class="card"><small>Total pendapatan</small><b><?= $rp($rev['s']) ?></b></div>
  <div class="card"><small>Analisis AI hari ini</small><b><?= (int) $AT['n'] ?></b></div>
</div>
<nav>
  <a href="?tab=users" class="<?= $tab === 'users' ? 'on' : '' ?>">Pengguna</a>
  <a href="?tab=orders" class="<?= $tab === 'orders' ? 'on' : '' ?>">Order</a>
  <a href="?tab=settings" class="<?= $tab === 'settings' ? 'on' : '' ?>">Pengaturan</a>
  <a href="?tab=ai" class="<?= $tab === 'ai' ? 'on' : '' ?>">Pemakaian AI</a>
</nav>

<?php if ($tab === 'users'):
    $where = [];
    $args = [];
    if ($q !== '') { $where[] = 'email LIKE ?'; $args[] = '%' . $q . '%'; }
    $cond = [
        'lifetime' => 'lifetime = 1',
        'monthly'  => 'lifetime = 0 AND paid_until > ?',
        'trial'    => 'lifetime = 0 AND (paid_until IS NULL OR paid_until <= ?) AND trial_ends_at > ?',
        'expired'  => 'lifetime = 0 AND (paid_until IS NULL OR paid_until <= ?) AND trial_ends_at <= ?',
    ];
    if ($stat !== '') {
        $where[] = '(' . $cond[$stat] . ')';
        $n = substr_count($cond[$stat], '?');
        for ($i = 0; $i < $n; $i++) $args[] = $nowS;
    }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $tot = $pdo->prepare("SELECT COUNT(*) FROM users $w");
    $tot->execute($args);
    $total = (int) $tot->fetchColumn();
    $st = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM ai_log l WHERE l.user_id = u.id AND l.cache_hit = 0 AND l.status = 'ok' AND l.created_at >= ?) AS ai_today,
        (SELECT COUNT(*) FROM ai_log l WHERE l.user_id = u.id AND l.status = 'ok') AS ai_all FROM users u $w ORDER BY u.id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
    $st->execute(array_merge([utc(ai_day_start())], $args));
    $users = $st->fetchAll();
?>
<div class="filters">
  <?php foreach (['' => 'Semua', 'trial' => 'Trial', 'monthly' => 'Bulanan', 'lifetime' => 'Lifetime', 'expired' => 'Kedaluwarsa'] as $k => $l): ?>
    <a href="<?= $h(admin_url(['st' => $k, 'p' => 1])) ?>" class="<?= $stat === $k ? 'on' : '' ?>"><?= $l ?></a>
  <?php endforeach; ?>
  <form method="get" class="inline" style="margin-left:10px"><input type="hidden" name="tab" value="users"><input type="hidden" name="st" value="<?= $h($stat) ?>"><input type="text" name="q" value="<?= $h($q) ?>" placeholder="cari email" style="width:200px"> <button>Cari</button></form>
</div><br>
<table><tr><th>Email</th><th>Status</th><th>Berlaku sampai</th><th>AI hari ini / total</th><th>Daftar</th><th>Aksi</th></tr>
<?php foreach ($users as $u): $a = access_info($u); $lim = ai_limit_for($u); ?>
<tr>
  <td><?= $h($u['email']) ?><br><small><?= $h($u['name']) ?></small></td>
  <td><span class="tag <?= $a['access'] ? 'ok' : 'bad' ?>"><?= $h($a['plan']) ?></span></td>
  <td><?= $a['until'] === null ? ($a['plan'] === 'lifetime' ? '∞' : '-') : $h(utc((int) $a['until'])) . ' UTC' ?></td>
  <td><?= (int) $u['ai_today'] ?><?= $lim === null ? '' : ' / ' . (int) $lim ?> · <?= (int) $u['ai_all'] ?></td>
  <td><?= $h($u['created_at']) ?></td>
  <td>
    <form class="inline" method="post"><?= $csrf ?><input type="hidden" name="uid" value="<?= (int) $u['id'] ?>"><input type="hidden" name="act" value="days"><input type="number" name="days" value="30" min="1"><button>+ hari</button></form>
    <form class="inline" method="post" onsubmit="return confirm('Jadikan lifetime?')"><?= $csrf ?><input type="hidden" name="uid" value="<?= (int) $u['id'] ?>"><input type="hidden" name="act" value="lifetime"><button>Lifetime</button></form>
    <form class="inline" method="post" onsubmit="return confirm('Cabut akses?')"><?= $csrf ?><input type="hidden" name="uid" value="<?= (int) $u['id'] ?>"><input type="hidden" name="act" value="revoke"><button>Cabut</button></form>
  </td>
</tr>
<?php endforeach; ?>
</table>
<p>
  <?= $total ?> akun · halaman <?= $page ?>/<?= max(1, (int) ceil($total / $per)) ?>
  <?php if ($page > 1): ?> · <a href="<?= $h(admin_url(['p' => $page - 1])) ?>">‹ sebelumnya</a><?php endif; ?>
  <?php if ($page * $per < $total): ?> · <a href="<?= $h(admin_url(['p' => $page + 1])) ?>">berikutnya ›</a><?php endif; ?>
</p>

<?php elseif ($tab === 'orders'):
    $orders = $pdo->query('SELECT o.*, u.email FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT 200')->fetchAll();
?>
<table><tr><th>Order</th><th>Email</th><th>Paket</th><th>Jumlah</th><th>Status</th><th>Metode</th><th>Dibuat</th><th>Dibayar</th></tr>
<?php foreach ($orders as $o): ?>
<tr><td><?= $h($o['order_id']) ?></td><td><?= $h($o['email']) ?></td><td><?= $h($o['plan']) ?></td><td><?= $rp($o['amount']) ?></td><td><span class="tag <?= $o['status'] === 'paid' ? 'ok' : '' ?>"><?= $h($o['status']) ?></span></td><td><?= $h($o['payment_type']) ?></td><td><?= $h($o['created_at']) ?></td><td><?= $h($o['paid_at']) ?></td></tr>
<?php endforeach; ?>
</table>

<?php elseif ($tab === 'settings'):
    $models = json_decode((string) setting('ai.models_cache', '[]'), true) ?: [];
?>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="save_sub">
  <h2 style="margin-top:0">Langganan</h2>
  <div class="grid">
  <?php foreach ($SET_SUB as $k => [$label, $type, $def, $help]): $pk = str_replace('.', '_', $k); ?>
    <label for="<?= $pk ?>"><?= $h($label) ?><?php if ($help): ?><small><?= $h($help) ?></small><?php endif; ?></label>
    <div><input class="w" id="<?= $pk ?>" type="number" min="0" name="<?= $pk ?>" value="<?= $h($S($k, $def)) ?>"></div>
  <?php endforeach; ?>
  </div>
  <p><small>Harga baru berlaku untuk transaksi berikutnya; pembayaran yang sudah berjalan tidak berubah.</small></p>
  <button class="btn">Simpan langganan</button>
</form>

<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="save_ai">
  <h2 style="margin-top:0">Analisis AI (kenari.id)</h2>
  <div class="grid">
  <?php foreach ($SET_AI as $k => [$label, $type, $def, $help]): $pk = str_replace('.', '_', $k); $val = $S($k, $def); ?>
    <label for="<?= $pk ?>"><?= $h($label) ?><?php if ($help): ?><small><?= $h($help) ?></small><?php endif; ?></label>
    <div>
    <?php if ($type === 'bool'): ?>
      <input type="checkbox" id="<?= $pk ?>" name="<?= $pk ?>" value="1" <?= $val === '1' ? 'checked' : '' ?>>
    <?php elseif ($type === 'secret'): ?>
      <input type="password" id="<?= $pk ?>" name="<?= $pk ?>" autocomplete="new-password" placeholder="<?= ai_key() !== '' ? '•••••••• tersimpan (' . $h(substr(ai_key(), 0, 3)) . '…' . $h(substr(ai_key(), -4)) . ')' : 'kn-…' ?>">
      <?php if (ai_key() !== ''): ?><br><label style="font-weight:400"><input type="checkbox" name="<?= $pk ?>_clear" value="1"> hapus key tersimpan</label><?php endif; ?>
    <?php elseif ($type === 'model'): ?>
      <input type="text" id="<?= $pk ?>" name="<?= $pk ?>" value="<?= $h($val) ?>" list="models" autocomplete="off">
    <?php elseif ($type === 'number'): ?>
      <input class="w" type="number" min="0" id="<?= $pk ?>" name="<?= $pk ?>" value="<?= $h($val) ?>">
    <?php else: ?>
      <input type="text" id="<?= $pk ?>" name="<?= $pk ?>" value="<?= $h($val) ?>">
    <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <datalist id="models"><?php foreach ($models as $m): ?><option value="<?= $h($m) ?>"><?php endforeach; ?></datalist>

  <h3>Prompt</h3>
  <p><small>Kosongkan untuk memakai prompt bawaan. Keluaran AI harus tetap berupa JSON dengan kunci: ringkasan, skor, label, temuan, peluang, risiko, rekomendasi, kata_kunci, saran_judul — bila skema diubah, bagian itu tidak akan tampil di extension.</small></p>
  <div class="grid">
  <?php foreach ($SET_PROMPT as $k => $label): $pk = str_replace('.', '_', $k); $kind = substr($k, 10);
        $defv = $kind === 'system' ? ai_default_system() : ai_default_prompt($kind); ?>
    <label for="<?= $pk ?>"><?= $h($label) ?></label>
    <div><textarea id="<?= $pk ?>" name="<?= $pk ?>" placeholder="<?= $h($defv) ?>"><?= $h($S($k)) ?></textarea></div>
  <?php endforeach; ?>
  </div>
  <p><button class="btn">Simpan pengaturan AI</button></p>
</form>

<div class="box">
  <form method="post" class="inline"><?= $csrf ?><input type="hidden" name="act" value="ai_test"><button class="btn sec">Tes koneksi</button></form>
  <form method="post" class="inline"><?= $csrf ?><input type="hidden" name="act" value="ai_models"><button class="btn sec">Muat daftar model</button></form>
  <small>Tes koneksi memakai pengaturan yang sudah <b>disimpan</b>. <?= $models ? count($models) . ' model termuat.' : '' ?></small>
</div>

<?php else: // ai
    $since = utc(time() - 14 * 86400);
    $daily = $pdo->prepare("SELECT DATE(DATE_ADD(created_at, INTERVAL 7 HOUR)) AS d, COUNT(*) AS n, SUM(cache_hit=1) AS c, SUM(status='error') AS e, SUM(tokens_in) AS ti, SUM(tokens_out) AS tu FROM ai_log WHERE created_at >= ? GROUP BY d ORDER BY d DESC");
    $daily->execute([$since]);
    $top = $pdo->prepare("SELECT u.email, COUNT(*) AS n, SUM(l.cache_hit=0 AND l.status='ok') AS fresh, SUM(l.tokens_in+l.tokens_out) AS tok FROM ai_log l JOIN users u ON u.id = l.user_id WHERE l.created_at >= ? GROUP BY l.user_id ORDER BY n DESC LIMIT 15");
    $top->execute([$since]);
    $logs = $pdo->query("SELECT l.*, u.email FROM ai_log l JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 60")->fetchAll();
    $cc = $pdo->query('SELECT COUNT(*) AS n, COALESCE(SUM(hits),0) AS h FROM ai_cache')->fetch();
?>
<div class="box">
  Status AI: <span class="tag <?= ai_on() ? 'ok' : 'bad' ?>"><?= ai_on() ? 'aktif' : (ai_key() === '' ? 'API key belum diisi' : 'dimatikan') ?></span>
  · model <b><?= $h($S('ai.model', 'deepseek-v4-flash')) ?></b>
  · cache: <?= (int) $cc['n'] ?> entri, <?= (int) $cc['h'] ?> kali terpakai ulang
  <form method="post" class="inline" onsubmit="return confirm('Kosongkan cache?')" style="margin-left:10px"><?= $csrf ?><input type="hidden" name="act" value="ai_clear_cache"><button>Kosongkan cache</button></form>
  <br><small>Hari ini: <?= (int) $AT['n'] ?> permintaan (<?= (int) $AT['c'] ?> dari cache, <?= (int) $AT['e'] ?> gagal) · token masuk <?= number_format((int) $AT['ti']) ?> / keluar <?= number_format((int) $AT['tu']) ?>. Biaya sebenarnya lihat dashboard kenari.id.</small>
</div>
<h2>14 hari terakhir (WIB)</h2>
<table><tr><th>Tanggal</th><th>Permintaan</th><th>Cache</th><th>Gagal</th><th>Token masuk</th><th>Token keluar</th></tr>
<?php foreach ($daily as $d): ?><tr><td><?= $h($d['d']) ?></td><td><?= (int) $d['n'] ?></td><td><?= (int) $d['c'] ?></td><td><?= (int) $d['e'] ?></td><td><?= number_format((int) $d['ti']) ?></td><td><?= number_format((int) $d['tu']) ?></td></tr><?php endforeach; ?>
</table>
<h2>Pengguna teraktif (14 hari)</h2>
<table><tr><th>Email</th><th>Permintaan</th><th>Analisis baru</th><th>Token</th></tr>
<?php foreach ($top as $t): ?><tr><td><?= $h($t['email']) ?></td><td><?= (int) $t['n'] ?></td><td><?= (int) $t['fresh'] ?></td><td><?= number_format((int) $t['tok']) ?></td></tr><?php endforeach; ?>
</table>
<h2>Log terbaru</h2>
<table><tr><th>Waktu (UTC)</th><th>Email</th><th>Jenis</th><th>Model</th><th>Hasil</th><th>ms</th><th>Token</th></tr>
<?php foreach ($logs as $l): ?><tr><td><?= $h($l['created_at']) ?></td><td><?= $h($l['email']) ?></td><td><?= $h($l['kind']) ?></td><td><?= $h($l['model']) ?></td>
<td><?php if ($l['status'] === 'error'): ?><span class="tag bad" title="<?= $h($l['error']) ?>">gagal</span> <small><?= $h($l['error']) ?></small><?php elseif ($l['cache_hit']): ?><span class="tag warn">cache</span><?php else: ?><span class="tag ok">ok</span><?php endif; ?></td>
<td><?= (int) $l['ms'] ?></td><td><?= (int) $l['tokens_in'] ?>/<?= (int) $l['tokens_out'] ?></td></tr><?php endforeach; ?>
</table>
<?php endif; ?>
</main></body></html>
