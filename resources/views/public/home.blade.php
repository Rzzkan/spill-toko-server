<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ config('app.name') }}</title>
<style>body{font:16px/1.6 system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0;background:#fff7f3;color:#222}
.c{background:#fff;border-radius:16px;padding:40px;max-width:520px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.08)}h1{margin:0 0 8px}p{color:#555}
a.b{display:inline-block;margin-top:12px;background:#ee4d2d;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600}</style></head>
<body><div class="c"><h1>🛍️ {{ config('app.name') }}</h1>
<p>Riset produk, toko, dan kata kunci Shopee langsung di halaman Shopee — lengkap dengan analisis AI.</p>
<p>Coba gratis {{ $trial }} hari. Bulanan Rp{{ number_format($monthly,0,',','.') }} · Lifetime Rp{{ number_format($lifetime,0,',','.') }}.</p>
@if($store)<a class="b" href="{{ $store }}">Pasang di Chrome</a>@endif</div></body></html>
