<?php

// Konfigurasi teknis (bukan rahasia). Semua yang perlu diubah admin ada di dashboard → Pengaturan.
return [
    'google_tokeninfo_url' => env('SPILL_GOOGLE_TOKENINFO_URL', 'https://oauth2.googleapis.com/tokeninfo'),
    // override URL Midtrans (hanya untuk pengujian); kosong = otomatis sandbox/production dari Pengaturan
    'midtrans_snap_url' => env('SPILL_MIDTRANS_SNAP_URL'),
    'midtrans_api_url' => env('SPILL_MIDTRANS_API_URL'),
    'display_timezone' => 'Asia/Jakarta',
];
