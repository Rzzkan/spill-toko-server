@php
    use App\Support\Fmt;
    $cta = route('audit');
    $paid = collect($plans)->filter(fn ($p) => ($p['price'] ?? 0) > 0);
    $faq = [
        ['Apa itu Marketplace Audit?', 'Alat untuk memeriksa kesehatan toko atau produk Shopee: judul, foto, deskripsi, rating, stok, penawaran, dan reputasi toko. Hasilnya skor, daftar temuan, dan rekomendasi prioritas dari AI.'],
        ['Bagaimana cara mengauditnya?', 'Tidak perlu pasang apa pun. Cukup unggah screenshot halaman produk/toko, atau halaman web yang kamu simpan sebagai PDF (Ctrl+P, Save as PDF). Panduan tertulisnya ada di halaman aplikasi.'],
        ['Bagaimana mengaudit iklan Shopee?', 'Unduh laporan CSV dari Seller Centre (Iklan Shopee, lalu pilih periode dan unduh data), kemudian unggah di aplikasi. Kami hitung ROAS, ACOS, CTR, dan konversi, menandai iklan yang boncos, lalu AI memberi rekomendasi. Nama toko, username, dan ID toko di file tidak dibaca.'],
        ['Apakah berkas yang saya unggah disimpan?', 'Tidak. Screenshot, PDF, dan CSV iklan yang kamu unggah hanya dibaca sekali untuk mengambil angka dan teks yang tampil, lalu langsung dibuang. Yang tersimpan hanya data hasil bacaan dan hasil audit, dihapus otomatis setelah ' . \App\Services\Audit::retentionHours() . ' jam.'],
        ['Apakah kata sandi atau akun Shopee saya diminta?', 'Tidak pernah. Kami tidak meminta kata sandi, cookie, atau akses ke akun Shopee-mu.'],
        ['Bagaimana cara membayar paket unlimited?', 'Lewat Midtrans: klik Berlangganan, masuk dengan Google, lalu bayar sesuai nominal paket di halaman Midtrans. Paket aktif otomatis setelah pembayaran berhasil dan tidak diperpanjang otomatis.'],
        ['Berapa jatah scan gratis?', $free . ' scan per bulan untuk tiap akun, reset setiap tanggal 1. Mau tanpa batas? Berlangganan unlimited.'],
        ['Scan yang gagal ikut terhitung?', 'Tidak. Jatah hanya terpakai bila audit berhasil selesai.'],
        ['Marketplace apa saja yang didukung?', 'Saat ini Shopee Indonesia. Marketplace lain menyusul.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="facebook-domain-verification" content="gzts236t5urmz20vp3wahb33svsly8" />
    <link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="apple-touch-icon" href="{{ asset('img/brand/temanseller-180.png') }}">
    <title>Marketplace Audit — Audit Toko &amp; Produk Shopee dengan AI | TemanSeller</title>
    <meta name="description" content="Audit toko dan produk Shopee otomatis: skor, temuan, dan rekomendasi AI. {{ $free }} scan gratis per bulan. Data dikumpulkan di browser kamu, tanpa menyimpan akun Shopee.">
    @vite(['resources/css/app.css'])
    <style>body{font-family:'Outfit',system-ui,sans-serif}.grad{background:radial-gradient(1100px 480px at 85% -10%,#FFE8C7 0,transparent 60%),#FFFAF2}summary::-webkit-details-marker{display:none}details[open] .chev{transform:rotate(180deg)}</style>
</head>
<body class="bg-white text-slate-800 antialiased">
<header class="sticky top-0 z-50 border-b border-orange-100/70 bg-white/85 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5">
        <x-brand :href="$hubUrl" size="size-12" class="text-lg" />
        <div class="flex items-center gap-2">
            <a href="{{ $hubUrl }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:text-ts-orange-text sm:block">Beranda</a>
            <a href="{{ $cta }}" class="rounded-lg bg-ts-orange px-4 py-2.5 text-sm font-semibold text-ts-navy shadow-sm hover:bg-ts-orange-dark">{{ $loggedIn ? 'Buka Audit' : 'Mulai gratis' }}</a>
        </div>
    </div>
</header>

<section class="grad">
    <div class="mx-auto max-w-4xl px-4 py-16 text-center md:py-24">
        <span class="rounded-full bg-white px-4 py-1.5 text-xs font-semibold text-ts-orange-text shadow-sm ring-1 ring-orange-100">Aplikasi web · Shopee</span>
        <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-ts-navy md:text-5xl">Periksa kesehatan toko &amp; produk Shopee-mu, pelan-pelan dibantu AI</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600">Unggah PDF halaman atau screenshot Shopee-mu, lalu AI memberi skor, temuan, dan langkah perbaikan yang bisa dikerjakan hari ini.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ $cta }}" class="rounded-xl bg-ts-orange px-7 py-3.5 text-base font-semibold text-ts-navy shadow-lg shadow-orange-200 hover:bg-ts-orange-dark">Coba {{ $free }} scan gratis</a>
        </div>
        <p class="mt-4 text-sm text-slate-500">Masuk dengan Google · {{ $free }} scan gratis tiap bulan</p>
        <div class="mx-auto mt-10 max-w-md overflow-hidden rounded-3xl border border-orange-100 bg-ts-cream shadow-xl shadow-orange-100"><img src="{{ asset('img/slides/audit.webp') }}" alt="Maskot TemanSeller dengan stetoskop dan papan ceklis audit" class="w-full" width="960" height="720"></div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16">
    <h2 class="text-center text-3xl font-bold text-ts-navy">Yang diperiksa</h2>
    <div class="mt-10 grid gap-5 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 p-6"><div class="text-2xl">📦</div><h3 class="mt-2 text-lg font-bold">Audit produk</h3><p class="mt-1 text-sm text-slate-600">Judul &amp; kata kunci, jumlah foto/video, kelengkapan deskripsi dan atribut, rating &amp; ulasan, stok, variasi, ongkir, diskon, dan saran judul baru.</p></div>
        <div class="rounded-2xl border border-slate-200 p-6"><div class="text-2xl">🏪</div><h3 class="mt-2 text-lg font-bold">Audit toko</h3><p class="mt-1 text-sm text-slate-600">Rating toko, kecepatan balas chat, pengikut, luas katalog, produk yang belum terjual, kualitas judul, dan badge toko.</p></div>
        <div class="rounded-2xl border border-slate-200 p-6"><div class="text-2xl">📈</div><h3 class="mt-2 text-lg font-bold">Audit iklan</h3><p class="mt-1 text-sm text-slate-600">Unggah laporan Shopee Ads (CSV): ROAS &amp; ACOS, iklan yang boncos, kata kunci tanpa konversi, dan iklan efisien yang layak ditambah anggaran.</p></div>
    </div>
    <div class="mt-10 grid gap-5 md:grid-cols-3">
        @foreach ([['01', 'Ambil bahan', 'Screenshot halaman produk/toko Shopee, atau simpan halamannya sebagai PDF (Ctrl+P). Ada panduan langkah demi langkah.'], ['02', 'Unggah', 'Kami baca angka dan teksnya, lalu berkasmu langsung dibuang, tidak disimpan.'], ['03', 'Terima hasil audit', 'Skor, pemeriksaan otomatis, dan rekomendasi prioritas dari AI.']] as [$n, $t, $d])
            <div class="rounded-2xl bg-orange-50/60 p-6"><div class="text-sm font-bold text-ts-orange-text">{{ $n }}</div><div class="mt-1 font-bold">{{ $t }}</div><p class="mt-1 text-sm text-slate-600">{{ $d }}</p></div>
        @endforeach
    </div>
</section>

<section class="bg-ts-cream/50">
    <div class="mx-auto max-w-5xl px-4 py-16">
        <h2 class="text-center text-3xl font-bold text-ts-navy">Contoh hasil audit</h2>
        <p class="mx-auto mt-3 max-w-2xl text-center text-slate-600">Beginilah tampilan hasilnya: skor, pemeriksaan otomatis, dan rekomendasi yang bisa langsung kamu kerjakan. <span class="text-slate-400">(Ilustrasi dengan data contoh.)</span></p>
        <div class="mx-auto mt-10 max-w-3xl rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-orange-100/60 sm:p-8" aria-label="Contoh tampilan hasil audit">
            <div class="flex flex-wrap items-center gap-5">
                <div class="grid size-24 shrink-0 place-items-center rounded-full text-3xl font-extrabold text-ts-navy" style="border:8px solid #FB8A12">72</div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Audit produk</div>
                    <div class="text-lg font-bold text-ts-navy">Kebaya Modern Brokat Wisuda (contoh)</div>
                    <span class="mt-2 inline-block rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-ts-orange-text">Cukup baik, masih bisa naik</span>
                </div>
            </div>
            <p class="mt-4 text-sm leading-relaxed text-slate-600">Produk sudah punya rating bagus dan ulasan yang cukup. Peluang terbesar ada di judul yang belum memuat kata kunci pencarian dan deskripsi yang masih pendek.</p>
            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div>
                    <div class="text-sm font-bold text-ts-navy">Pemeriksaan otomatis</div>
                    <div class="mt-2 divide-y divide-slate-100 text-sm">
                        @foreach ([['ok', 'Rating produk 4,8', 'Di atas rata-rata kategori.'], ['ok', 'Stok tersedia', 'Aman untuk periode promo.'], ['warn', 'Deskripsi 180 karakter', 'Disarankan minimal 500 karakter.'], ['bad', 'Tidak ada video produk', 'Video menaikkan minat beli.']] as [$s, $tt, $d])
                            <div class="flex gap-3 py-2.5"><span class="grid size-6 shrink-0 place-items-center rounded-full text-xs font-bold {{ $s === 'ok' ? 'bg-emerald-50 text-emerald-700' : ($s === 'warn' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">{{ $s === 'ok' ? '✓' : ($s === 'warn' ? '!' : '✕') }}</span><div><div class="font-medium text-slate-800">{{ $tt }}</div><div class="text-slate-500">{{ $d }}</div></div></div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="text-sm font-bold text-ts-navy">Rekomendasi prioritas</div>
                    <ol class="mt-2 space-y-3 text-sm">
                        @foreach ([['Tambahkan kata kunci di judul', 'Tinggi', 'Mis. “Kebaya Brokat Modern Wisuda Wanita”.'], ['Perpanjang deskripsi', 'Sedang', 'Cantumkan bahan, ukuran, dan cara perawatan.'], ['Tambah video 15–30 detik', 'Sedang', 'Tunjukkan detail jahitan dan jatuhnya kain.']] as $i => [$a, $pr, $al])
                            <li class="flex gap-3"><span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-ts-cream text-xs font-bold text-ts-orange-text">{{ $i + 1 }}</span><div><div class="font-medium text-slate-800">{{ $a }} <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-500">{{ $pr }}</span></div><div class="text-slate-500">{{ $al }}</div></div></li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50">
    <div class="mx-auto max-w-4xl px-4 py-14 text-center">
        <h2 class="text-3xl font-bold text-ts-navy">Privasi dulu</h2>
        <p class="mx-auto mt-4 max-w-2xl text-slate-600">Screenshot dan PDF yang kamu unggah <b>tidak disimpan</b>: hanya dibaca sekali untuk mengambil angka dan teks yang tampil, lalu langsung dibuang. Kami tidak pernah meminta kata sandi, cookie, atau akses akun Shopee-mu. Data hasil bacaan dan hasil audit dihapus otomatis setelah {{ \App\Services\Audit::retentionHours() }} jam.</p>
    </div>
</section>

<section class="mx-auto max-w-5xl px-4 py-16">
    <h2 class="text-center text-3xl font-bold text-ts-navy">Harga</h2>
    <div class="mt-10 grid gap-5 md:grid-cols-{{ 1 + max(1, $paid->count()) }}">
        <div class="rounded-2xl border border-slate-200 p-6"><div class="font-bold">Gratis</div><div class="mt-2 text-3xl font-extrabold">Rp0</div><p class="mt-1 text-sm text-slate-500">{{ $free }} scan / bulan</p><a href="{{ $cta }}" class="mt-5 block rounded-xl border border-slate-200 py-2.5 text-center text-sm font-semibold hover:border-ts-orange">Mulai</a></div>
        @forelse ($paid as $k => $p)
            <div class="rounded-2xl border-2 border-ts-orange p-6"><div class="font-bold">Unlimited · {{ $p['label'] }}</div><div class="mt-2 text-3xl font-extrabold">{{ Fmt::rp($p['price']) }}</div><p class="mt-1 text-sm text-slate-500">Scan tanpa batas · {{ $p['days'] }} hari</p><a href="{{ url('/beli/audit/' . $k) }}" class="mt-5 block rounded-xl bg-ts-orange py-2.5 text-center text-sm font-semibold text-ts-navy hover:bg-ts-orange-dark">Berlangganan</a></div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-sm text-slate-500"><div class="font-bold text-slate-700">Unlimited</div><p class="mt-2">Paket scan tanpa batas segera hadir.</p></div>
        @endforelse
    </div>
    <div class="mx-auto mt-8 max-w-3xl rounded-2xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-600">
        <div class="font-bold text-ts-navy">Info pembayaran</div>
        <ul class="mt-3 space-y-2">
            <li><b>Bayar lewat Midtrans.</b> Klik <i>Berlangganan</i>, masuk dengan Google, lalu kamu diarahkan ke halaman pembayaran Midtrans dengan nominal sesuai paket. Pilihan metode (misalnya QRIS, transfer virtual account, atau e-wallet) tampil di halaman itu.</li>
            <li><b>Aktif otomatis.</b> Setelah pembayaran berhasil, scan tanpa batas langsung aktif di akunmu selama masa paket.</li>
            <li><b>Tanpa perpanjangan otomatis.</b> Kamu hanya membayar sekali per periode. Saat habis, kamu bisa memperpanjang atau kembali ke paket gratis.</li>
            <li><b>Gratis dulu boleh.</b> Pilih <i>Mulai</i> untuk memakai jatah gratis tanpa data pembayaran.</li>
            <li><b>Ada kendala pembayaran?</b> @if (\App\Services\Layanan::waUrl('Halo TemanSeller, saya butuh bantuan pembayaran Marketplace Audit.'))<a href="{{ \App\Services\Layanan::waUrl('Halo TemanSeller, saya butuh bantuan pembayaran Marketplace Audit.') }}" target="_blank" rel="noopener" class="font-semibold text-ts-orange-text underline">Hubungi kami via WhatsApp</a>.@else Hubungi kami lewat halaman akun.@endif</li>
        </ul>
    </div>
</section>

<section class="mx-auto max-w-3xl px-4 pb-20">
    <h2 class="text-center text-3xl font-bold text-ts-navy">Pertanyaan umum</h2>
    <div class="mt-8 space-y-3">
        @foreach ($faq as [$q, $a])
            <details class="rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer items-center justify-between font-semibold">{{ $q }}<span class="chev text-slate-400 transition">⌄</span></summary><p class="mt-3 text-sm text-slate-600">{{ $a }}</p></details>
        @endforeach
    </div>
</section>
<footer class="border-t border-slate-100 py-8 text-center text-xs text-slate-400"><a href="{{ url('/privasi') }}" class="hover:text-ts-orange-text">Kebijakan privasi</a> · TemanSeller · v{{ config('temanseller.version') }}</footer>
</body>
</html>
