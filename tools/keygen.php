<?php
// Jalankan sekali: php tools/keygen.php
// - license_sign_key & jwt_secret  -> masukkan ke config.php (RAHASIA, hanya di server)
// - LICENSE_PUBKEY                 -> masukkan ke extension (src/config.js), aman dipublikasikan
$kp = sodium_crypto_sign_keypair();
echo "license_sign_key (config.php) : " . base64_encode(sodium_crypto_sign_secretkey($kp)) . PHP_EOL;
echo "LICENSE_PUBKEY (extension)    : " . base64_encode(sodium_crypto_sign_publickey($kp)) . PHP_EOL;
echo "jwt_secret (config.php)       : " . bin2hex(random_bytes(32)) . PHP_EOL;
