<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Terima kasih - {{ config('app.name') }}</title>
<style>body{font:16px/1.6 system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0;background:#fafafa;color:#222}
.c{background:#fff;border-radius:14px;padding:36px;max-width:440px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.08)}h1{margin:0 0 8px;font-size:24px}p{color:#555}.i{font-size:46px}</style></head>
<body><div class="c"><div class="i">✅</div><h1>Terima kasih!</h1>
<p>Pembayaran Anda sedang diproses {!! $order ? '(<code>'.e($order).'</code>)' : '' !!}. Biasanya aktif dalam hitungan detik.</p>
<p>Kembali ke tab Shopee — extension akan mengaktifkan akses Anda otomatis. Halaman ini boleh ditutup.</p></div></body></html>
