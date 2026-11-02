<?php
// Membuat config.php dari environment. Dipanggil oleh setup.sh:  php make-config.php /var/www/spilltoko/config.php
// Bila config.php sudah ada, kunci lisensi & JWT secret DIPERTAHANKAN (jangan sampai berubah: public key di extension akan tidak cocok).
$out = $argv[1] ?? '';
if ($out === '') { fwrite(STDERR, "pemakaian: php make-config.php <path config.php>\n"); exit(1); }
$old = is_file($out) ? (require $out) : [];
$env = fn(string $k, string $d = '') => (string) (getenv($k) !== false && getenv($k) !== '' ? getenv($k) : $d);

$licKey = $old['license_sign_key'] ?? '';
$jwt = $old['jwt_secret'] ?? '';
if ($licKey === '' || base64_decode($licKey, true) === false) {
    $kp = sodium_crypto_sign_keypair();
    $licKey = base64_encode(sodium_crypto_sign_secretkey($kp));
}
if ($jwt === '') $jwt = bin2hex(random_bytes(32));
$pub = base64_encode(sodium_crypto_sign_publickey_from_secretkey(base64_decode($licKey)));

$adminHash = $old['admin']['pass_hash'] ?? '';
if ($env('ADMIN_PASS') !== '') $adminHash = password_hash($env('ADMIN_PASS'), PASSWORD_DEFAULT);

$cfg = $old;
$cfg['app_url'] = rtrim($env('APP_URL', $old['app_url'] ?? ''), '/');
$cfg['chrome_store_url'] = $env('CHROME_STORE_URL', $old['chrome_store_url'] ?? '#');
$cfg['db'] = ['dsn' => 'mysql:host=localhost;dbname=' . $env('DB_NAME', 'spilltoko') . ';charset=utf8mb4',
    'user' => $env('DB_USER', 'spilltoko'), 'pass' => $env('DB_PASS', $old['db']['pass'] ?? '')];
$cfg['google_client_id'] = $env('GOOGLE_CLIENT_ID', $old['google_client_id'] ?? '');
$cfg['midtrans'] = ['server_key' => $env('MIDTRANS_SERVER_KEY', $old['midtrans']['server_key'] ?? ''),
    'is_production' => $env('MIDTRANS_PROD') !== '' ? $env('MIDTRANS_PROD') === '1' : (bool) ($old['midtrans']['is_production'] ?? false)];
$cfg['jwt_secret'] = $jwt;
$cfg['license_sign_key'] = $licKey;
$cfg['plans'] = $old['plans'] ?? ['monthly' => ['label' => 'Bulanan', 'price' => 25000, 'days' => 30], 'lifetime' => ['label' => 'Lifetime', 'price' => 200000, 'days' => null]];
$cfg['trial_days'] = $old['trial_days'] ?? 7;
$cfg['license_ttl_hours'] = $old['license_ttl_hours'] ?? 72;
$cfg['admin'] = ['user' => $env('ADMIN_USER', $old['admin']['user'] ?? 'admin'), 'pass_hash' => $adminHash];

$tmp = $out . '.tmp';
file_put_contents($tmp, "<?php\n// Dibuat oleh deploy/setup.sh - jangan di-commit. Harga/trial/AI bisa diubah dari halaman admin.\nreturn " . var_export($cfg, true) . ";\n");
chmod($tmp, 0640);
rename($tmp, $out);
echo $pub, PHP_EOL;   // baris terakhir = LICENSE_PUBKEY untuk extension
