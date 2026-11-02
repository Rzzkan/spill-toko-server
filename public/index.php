<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
$plans = cfg('plans');
$trial = (int) cfg('trial_days', 7);
$store = (string) cfg('chrome_store_url', '#');
$rp = fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Spill Toko - Riset Produk & Toko Shopee</title>
<meta name="description" content="Extension Chrome untuk riset produk terlaris, omset toko kompetitor, dan market size di Shopee.">
<style>
:root{--acc:#ee4d2d;--fg:#1b1b1f;--mut:#666}*{box-sizing:border-box}
body{margin:0;font:16px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:var(--fg);background:#fff}
.wrap{max-width:1000px;margin:0 auto;padding:0 20px}
header{border-bottom:1px solid #eee}header .wrap{display:flex;align-items:center;justify-content:space-between;height:60px}
.logo{font-weight:800;font-size:20px}.logo span{color:var(--acc)}
.btn{display:inline-block;background:var(--acc);color:#fff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:600}
.btn.o{background:#fff;color:var(--acc);border:1.5px solid var(--acc)}
.hero{padding:72px 0 40px;text-align:center}.hero h1{font-size:clamp(30px,5vw,48px);line-height:1.15;margin:0 0 16px}
.hero p{color:var(--mut);max-width:620px;margin:0 auto 28px;font-size:18px}
.grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));margin:30px 0}
.f{background:#fafafa;border:1px solid #eee;border-radius:12px;padding:20px}.f h3{margin:0 0 6px;font-size:17px}.f p{margin:0;color:var(--mut);font-size:15px}
h2{text-align:center;font-size:30px;margin:60px 0 8px}.sub{text-align:center;color:var(--mut);margin:0 0 28px}
.price{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));max-width:720px;margin:0 auto}
.p{border:1.5px solid #eee;border-radius:14px;padding:26px;text-align:center}.p.hot{border-color:var(--acc);box-shadow:0 6px 24px rgba(238,77,45,.12)}
.p b{font-size:34px;display:block;margin:8px 0}.p small{color:var(--mut)}.p ul{list-style:none;padding:0;margin:16px 0;text-align:left;color:#333}.p li:before{content:"✓ ";color:var(--acc);font-weight:700}
.note{max-width:720px;margin:26px auto;color:var(--mut);font-size:14px}
details{border-bottom:1px solid #eee;padding:14px 0}summary{cursor:pointer;font-weight:600}details p{color:var(--mut);margin:8px 0 0}
footer{margin-top:70px;border-top:1px solid #eee;padding:26px 0;color:var(--mut);font-size:13px;text-align:center}
</style></head><body>
<header><div class="wrap"><div class="logo">Spill <span>Toko</span></div><a class="btn" href="<?= htmlspecialchars($store) ?>">Pasang Extension</a></div></header>
<section class="hero wrap">
  <h1>Bongkar omset toko &amp; produk kompetitor di Shopee</h1>
  <p>Riset langsung dari halaman Shopee: estimasi omset, volume penjualan, market size, dan produk andalan toko. Export ke CSV / Google Sheets.</p>
  <a class="btn" href="<?= htmlspecialchars($store) ?>">Coba gratis <?= $trial ?> hari</a>
</section>
<section class="wrap"><div class="grid">
  <div class="f"><h3>Riset pencarian &amp; kategori</h3><p>Rentang harga, total terjual, omset, dan market share tiap produk dari hasil pencarian kata kunci.</p></div>
  <div class="f"><h3>Spill toko kompetitor</h3><p>Omset, volume, produk andalan, dan info toko dalam satu kartu di halaman toko.</p></div>
  <div class="f"><h3>Detail produk</h3><p>Rating, stok, wishlist, distribusi bintang, % komplain, dan produk serupa.</p></div>
  <div class="f"><h3>Analisa Detail</h3><p>Tampilan Grid &amp; Table lengkap dengan urutkan, filter, dan Download CSV.</p></div>
</div></section>
<section class="wrap">
  <h2>Harga</h2><p class="sub">Coba gratis <?= $trial ?> hari. Tanpa kartu kredit.</p>
  <div class="price">
    <div class="p"><h3><?= htmlspecialchars($plans['monthly']['label']) ?></h3><b><?= $rp($plans['monthly']['price']) ?></b><small>per <?= (int) $plans['monthly']['days'] ?> hari</small>
      <ul><li>Semua fitur</li><li>Riset tanpa batas</li><li>Perpanjang kapan saja</li></ul></div>
    <div class="p hot"><h3><?= htmlspecialchars($plans['lifetime']['label']) ?></h3><b><?= $rp($plans['lifetime']['price']) ?></b><small>sekali bayar</small>
      <ul><li>Semua fitur selamanya</li><li>Gratis update aplikasi</li><li>Tanpa perpanjangan</li></ul></div>
  </div>
  <p class="note">Pembayaran lewat Midtrans (QRIS, Virtual Account, e-wallet, kartu). Paket bulanan bersifat prabayar: tidak ada penarikan otomatis, perpanjang manual saat masa aktif habis. Login memakai akun Google dari dalam extension.</p>
</section>
<section class="wrap" style="max-width:760px">
  <h2>Pertanyaan umum</h2><div style="height:16px"></div>
  <details><summary>Bagaimana cara berlangganan?</summary><p>Pasang extension, klik “Masuk dengan Google” (trial <?= $trial ?> hari langsung aktif), lalu pilih paket di dalam extension dan selesaikan pembayaran.</p></details>
  <details><summary>Apakah data saya dikirim ke server?</summary><p>Hasil scan Shopee disimpan lokal di browser Anda. Server hanya menyimpan email Google dan status langganan.</p></details>
  <details><summary>Apakah angka omset akurat?</summary><p>Angka dihitung dari harga × jumlah terjual yang tampil di Shopee. Karena Shopee membulatkan angka terjual (mis. “10RB+”), hasilnya berupa estimasi.</p></details>
  <details><summary>Apa itu update gratis di paket Lifetime?</summary><p>Anda mendapat semua pembaruan fitur extension tanpa biaya tambahan, selama produk ini tersedia.</p></details>
</section>
<footer>© <?= date('Y') ?> Spill Toko. Tidak berafiliasi dengan Shopee.</footer>
</body></html>
