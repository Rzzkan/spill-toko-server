@php
    use App\Support\Fmt;
    $cta = route('audit');
    $paid = collect($plans)->filter(fn ($p) => ($p['price'] ?? 0) > 0);
    $faq = [
        ['Apa itu Marketplace Audit?', 'Alat untuk memeriksa kesehatan toko atau produk Shopee: judul, foto, deskripsi, rating, stok, penawaran, dan reputasi toko. Hasilnya skor, daftar temuan, dan rekomendasi prioritas dari AI.'],
        ['Apakah saya harus login Shopee?', 'Ya. Data dikumpulkan di browser kamu sendiri lewat extension, jadi kamu perlu sedang login di Shopee. Kalau belum, kami akan memintamu login dulu.'],
        ['Apakah kata sandi atau akun Shopee saya disimpan?', 'Tidak. Kami tidak membaca atau menyimpan kata sandi, cookie, maupun sesi login Shopee-mu. Server hanya menerima data publik toko/produk yang kamu audit, dan menghapusnya otomatis setelah ' . \App\Services\Audit::retentionHours() . ' jam.'],
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
        <span class="rounded-full bg-white px-4 py-1.5 text-xs font-semibold text-ts-orange-text shadow-sm ring-1 ring-orange-100">Web + Extension Chrome · Shopee</span>
        <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-ts-navy md:text-5xl">Periksa kesehatan toko &amp; produk Shopee-mu, pelan-pelan dibantu AI</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600">Tempel tautan, kami kumpulkan datanya langsung dari browser-mu, lalu AI memberi skor, temuan, dan langkah perbaikan yang bisa dikerjakan hari ini.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ $cta }}" class="rounded-xl bg-ts-orange px-7 py-3.5 text-base font-semibold text-ts-navy shadow-lg shadow-orange-200 hover:bg-ts-orange-dark">Coba {{ $free }} scan gratis</a>
            @if ($ext)<a href="{{ $ext }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-200 bg-white px-7 py-3.5 text-base font-semibold text-slate-700 hover:border-ts-orange">Pasang extension ↗</a>@endif
        </div>
        <p class="mt-4 text-sm text-slate-500">Masuk dengan Google · {{ $free }} scan gratis tiap bulan</p>
        <div class="mx-auto mt-10 max-w-md overflow-hidden rounded-3xl border border-orange-100 bg-ts-cream shadow-xl shadow-orange-100"><img src="{{ asset('img/slides/audit.webp') }}" alt="Maskot TemanSeller dengan stetoskop dan papan ceklis audit" class="w-full" width="960" height="720"></div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16">
    <h2 class="text-center text-3xl font-bold text-ts-navy">Yang diperiksa</h2>
    <div class="mt-10 grid gap-5 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 p-6"><div class="text-2xl">📦</div><h3 class="mt-2 text-lg font-bold">Audit produk</h3><p class="mt-1 text-sm text-slate-600">Judul &amp; kata kunci, jumlah foto/video, kelengkapan deskripsi dan atribut, rating &amp; ulasan, stok, variasi, ongkir, diskon, dan saran judul baru.</p></div>
        <div class="rounded-2xl border border-slate-200 p-6"><div class="text-2xl">🏪</div><h3 class="mt-2 text-lg font-bold">Audit toko</h3><p class="mt-1 text-sm text-slate-600">Rating toko, kecepatan balas chat, pengikut, luas katalog, produk yang belum terjual, kualitas judul, dan badge toko.</p></div>
    </div>
    <div class="mt-10 grid gap-5 md:grid-cols-3">
        @foreach ([['01', 'Tempel tautan', 'Salin tautan toko atau produk dari Shopee.'], ['02', 'Kumpulkan di browser-mu', 'Extension membaca halaman Shopee yang kamu buka sendiri (kamu harus login Shopee).'], ['03', 'Terima hasil audit', 'Skor, pemeriksaan otomatis, dan rekomendasi prioritas dari AI.']] as [$n, $t, $d])
            <div class="rounded-2xl bg-orange-50/60 p-6"><div class="text-sm font-bold text-ts-orange-text">{{ $n }}</div><div class="mt-1 font-bold">{{ $t }}</div><p class="mt-1 text-sm text-slate-600">{{ $d }}</p></div>
        @endforeach
    </div>
</section>

<section class="bg-slate-50">
    <div class="mx-auto max-w-4xl px-4 py-14 text-center">
        <h2 class="text-3xl font-bold text-ts-navy">Privasi dulu</h2>
        <p class="mx-auto mt-4 max-w-2xl text-slate-600">Aplikasi ini <b>tidak menyimpan apa pun dari akun Shopee-mu</b>: tanpa kata sandi, tanpa cookie, tanpa sesi login. Pengumpulan data berjalan di browser-mu. Yang sampai ke server hanya data publik toko/produk yang kamu minta diaudit, dan semuanya dihapus otomatis setelah {{ \App\Services\Audit::retentionHours() }} jam.</p>
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
