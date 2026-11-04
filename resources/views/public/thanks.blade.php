<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="apple-touch-icon" href="{{ asset('img/brand/temanseller-180.png') }}"><title>Status pembayaran - {{ config('app.name') }}</title>
<style>body{font:16px/1.6 system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0;background:#fafafa;color:#222}
.c{background:#fff;border-radius:14px;padding:36px;max-width:440px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.08)}h1{margin:0 0 8px;font-size:24px}p{color:#555}.i{font-size:46px}
.b{display:inline-block;margin-top:6px;padding:10px 20px;border-radius:10px;background:#ee4d2d;color:#fff;text-decoration:none;font-weight:600}</style></head>
@php
    $ref = $order ? ' (<code>' . e($order) . '</code>)' : '';
    $m = [
        'paid' => ['✅', 'Pembayaran berhasil', 'Terima kasih! Pembayaran Anda' . $ref . ' sudah kami terima dan paket sudah aktif.', 'Kembali ke tab Shopee — extension akan mengaktifkan akses Anda otomatis. Halaman ini boleh ditutup.'],
        'pending' => ['⏳', 'Pembayaran belum selesai', 'Kami belum menerima pembayaran untuk pesanan ini' . $ref . '. Jika Anda sudah membayar, tunggu beberapa saat lalu muat ulang halaman ini.', 'Kalau Anda menutup halaman pembayaran, silakan buat pesanan baru dari halaman akun.'],
        'cancelled' => ['❌', 'Pembayaran dibatalkan', 'Pesanan' . $ref . ' dibatalkan dan tidak ada tagihan yang diproses.', 'Anda bisa memilih paket lagi kapan saja.'],
        'expired' => ['⌛', 'Pembayaran kedaluwarsa', 'Batas waktu pembayaran untuk pesanan' . $ref . ' sudah lewat.', 'Silakan buat pesanan baru dari halaman akun.'],
        'denied' => ['❌', 'Pembayaran ditolak', 'Pembayaran untuk pesanan' . $ref . ' ditolak oleh penyedia pembayaran.', 'Coba metode pembayaran lain dari halaman akun.'],
        'unknown' => ['ℹ️', 'Pesanan tidak ditemukan', 'Kami tidak menemukan pesanan dengan nomor ini.', 'Cek status langganan Anda di halaman akun.'],
    ][$status] ?? ['ℹ️', 'Status pembayaran', 'Status pesanan belum diketahui.', ''];
@endphp
<body><div class="c"><div class="i">{{ $m[0] }}</div><h1>{{ $m[1] }}</h1>
<p>{!! $m[2] !!}</p>
@if ($m[3])<p>{{ $m[3] }}</p>@endif
@if ($status === 'paid')<p><a href="{{ url('/akun') }}">Lihat status langganan →</a></p>@else<p><a class="b" href="{{ url('/akun') }}">{{ in_array($status, ['cancelled', 'expired', 'denied'], true) ? 'Pilih paket lagi' : 'Buka halaman akun' }}</a></p>@endif
</div></body></html>
