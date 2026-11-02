<?php
// Salin file ini menjadi config.php (di luar folder public/) lalu isi nilainya.
// JANGAN pernah meng-commit config.php.
return [
    // Domain publik tanpa slash akhir (dipakai untuk halaman "terima kasih" setelah bayar)
    'app_url' => 'https://spilltoko.example.com',
    'chrome_store_url' => '#',

    'db' => [
        'dsn'  => 'mysql:host=localhost;dbname=spilltoko;charset=utf8mb4',
        'user' => 'spilltoko',
        'pass' => 'ganti-password-db',
    ],

    // OAuth Client ID (tipe "Web application") dari Google Cloud Console
    'google_client_id' => 'xxxxxxxx.apps.googleusercontent.com',

    'midtrans' => [
        'server_key'    => 'SB-Mid-server-xxxxxxxx',   // sandbox: SB-Mid-server-..., production: Mid-server-...
        'is_production' => false,
    ],

    // Hasil `php tools/keygen.php`
    'jwt_secret'       => 'GANTI-DENGAN-RANDOM-64-KARAKTER',
    'license_sign_key' => 'GANTI-DENGAN-BASE64-SECRET-KEY-ED25519',

    // Paket & harga (Rupiah). days = null berarti seumur hidup.
    'plans' => [
        'monthly'  => ['label' => 'Bulanan',  'price' => 25000,  'days' => 30],
        'lifetime' => ['label' => 'Lifetime', 'price' => 200000, 'days' => null],
    ],
    'trial_days'        => 7,
    'license_ttl_hours' => 72,   // batas offline: extension harus cek ulang ke server maksimal tiap 72 jam

    // Halaman admin (/admin). Buat hash: php -r "echo password_hash('PASSWORDMU', PASSWORD_DEFAULT);"
    'admin' => [
        'user'      => 'admin',
        'pass_hash' => '',
    ],
];
