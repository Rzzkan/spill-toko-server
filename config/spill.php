<?php

// Konfigurasi teknis (bukan rahasia). Semua yang perlu diubah admin ada di dashboard → Pengaturan.
return [
    'google_tokeninfo_url' => env('SPILL_GOOGLE_TOKENINFO_URL', 'https://oauth2.googleapis.com/tokeninfo'),
    // override URL Midtrans (hanya untuk pengujian); kosong = otomatis sandbox/production dari Pengaturan
    'midtrans_snap_url' => env('SPILL_MIDTRANS_SNAP_URL'),
    'midtrans_api_url' => env('SPILL_MIDTRANS_API_URL'),
    // versi aplikasi server (naik tiap pembaruan; file VERSION di root). Tampil di admin, landing, portal.
    'version' => is_file(base_path('VERSION')) ? trim((string) file_get_contents(base_path('VERSION'))) : 'dev',
    // batas browser (instalasi extension) per akun
    'max_devices' => (int) env('SPILL_MAX_DEVICES', 2),
    'display_timezone' => 'Asia/Jakarta',
];
