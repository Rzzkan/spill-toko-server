@php
    use App\Support\Fmt;
    $cta = $loggedIn ? route('swara.page') : route('portal.login');
    $lifetime = $plans['lifetime'] ?? null;
    $faq = [
        ['Apa itu Swara?', 'Alat cloning suara: rekam atau unggah contoh suaramu, ketik teks, lalu diubah jadi audio dengan suara itu. Mesinnya (OmniVoice) berjalan di Google Colab milik kamu sendiri — gratis dari Google — bukan di server TemanSeller.'],
        ['Kenapa harus pakai Colab sendiri?', 'Supaya biayanya tidak membengkak kalau penggunanya banyak, dan supaya audio suaramu tidak pernah melewati server kami. Kamu hanya menjalankan satu notebook, menyalin link yang muncul, lalu menempelkannya di halaman Swara.'],
        ['Apakah ini sekali bayar?', 'Ya. Satu kali beli memberi akses memakai Swara selama aplikasinya masih kami operasikan — bukan biaya bulanan.'],
        ['Apakah suara saya disimpan di server TemanSeller?', 'Tidak. Audio referensi dan hasilnya dikirim langsung dari browser kamu ke Colab kamu sendiri, tidak lewat server kami sama sekali. Server kami hanya memeriksa lisensi dan menerbitkan izin pakai yang berumur pendek.'],
        ['Bisa dipakai di banyak perangkat sekaligus?', 'Satu lisensi untuk satu sesi notebook yang aktif. Menjalankan notebook baru otomatis mengambil alih dari sesi lama.'],
        ['Apakah boleh meng-clone suara orang lain?', 'Hanya dengan izin pemilik suaranya. Dilarang dipakai untuk menipu atau menyamar sebagai orang lain. Sebaiknya pakai suaramu sendiri.'],
        ['Kenapa kadang notebook berhenti sendiri?', 'Google Colab gratis punya batas waktu sesi dan ketersediaan GPU yang berubah-ubah. Kalau putus, jalankan ulang notebook dan tempel link barunya.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="facebook-domain-verification" content="gzts236t5urmz20vp3wahb33svsly8" />
    <link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="apple-touch-icon" href="{{ asset('img/brand/temanseller-180.png') }}">
    <title>Swara — Cloning Suara, Jalan di Google Colab Kamu | TemanSeller</title>
    <meta name="description" content="Swara: ubah teks jadi audio dengan suaramu sendiri. Mesinnya jalan gratis di Google Colab kamu, bukan di server kami. Sekali beli, bukan langganan.">
    @vite(['resources/css/app.css'])
    <style>body{font-family:'Outfit',system-ui,sans-serif}.grad{background:radial-gradient(1100px 480px at 85% -10%,#FFE8C7 0,transparent 60%),#FFFAF2}summary::-webkit-details-marker{display:none}details[open] .chev{transform:rotate(180deg)}</style>
</head>
<body class="bg-white text-slate-800 antialiased">
<header class="sticky top-0 z-50 border-b border-orange-100/70 bg-white/85 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5">
        <x-brand href="{{ url('/') }}" size="size-12" class="text-lg" />
        <div class="flex items-center gap-2">
            <a href="{{ url('/') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:text-ts-orange-text sm:block">Beranda</a>
            <a href="{{ $cta }}" class="rounded-lg bg-ts-orange px-4 py-2.5 text-sm font-semibold text-ts-navy shadow-sm hover:bg-ts-orange-dark">{{ $loggedIn ? 'Buka Swara' : 'Masuk' }}</a>
        </div>
    </div>
</header>

<section class="grad">
    <div class="mx-auto max-w-4xl px-4 py-16 text-center md:py-24">
        <span class="rounded-full bg-white px-4 py-1.5 text-xs font-semibold text-ts-orange-text shadow-sm ring-1 ring-orange-100">Aplikasi web · Cloning suara</span>
        <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-ts-navy md:text-5xl">Swara — ubah teks jadi suaramu sendiri</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600">Rekam contoh suaramu, ketik naskahnya, lalu Swara menghasilkan audio dengan suara itu. Mesinnya jalan di Google Colab kamu sendiri — gratis, dan suaramu tidak pernah melewati server kami.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ $cta }}" class="rounded-xl bg-ts-orange px-7 py-3.5 text-base font-semibold text-ts-navy shadow-lg shadow-orange-200 hover:bg-ts-orange-dark">{{ $loggedIn ? 'Buka Swara' : 'Masuk dengan Google' }}</a>
        </div>
        <p class="mt-4 text-sm text-slate-500">Sekali beli · bukan langganan bulanan</p>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16">
    <h2 class="text-center text-3xl font-bold text-ts-navy">Cara kerjanya</h2>
    <div class="mt-10 grid gap-5 md:grid-cols-3">
        @foreach ([['01', 'Jalankan notebook', 'Buka notebook Swara di Google Colab (gratis), jalankan, lalu salin link yang muncul.'], ['02', 'Tempel link di Swara', 'Masuk ke halaman Swara di TemanSeller, tempel link itu. Kami periksa lisensimu lebih dulu.'], ['03', 'Rekam & hasilkan', 'Unggah contoh suaramu, ketik naskah, lalu dengarkan dan unduh hasilnya langsung dari browser.']] as [$n, $t, $d])
            <div class="rounded-2xl bg-orange-50/60 p-6"><div class="text-sm font-bold text-ts-orange-text">{{ $n }}</div><div class="mt-1 font-bold">{{ $t }}</div><p class="mt-1 text-sm text-slate-600">{{ $d }}</p></div>
        @endforeach
    </div>
    <div class="mt-10 grid gap-5 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 p-6"><div class="text-2xl">🔒</div><h3 class="mt-2 text-lg font-bold">Audio tidak lewat server kami</h3><p class="mt-1 text-sm text-slate-600">Browser kamu bicara langsung ke Colab kamu sendiri. Server TemanSeller hanya memeriksa lisensi dan menerbitkan izin pakai yang berumur pendek (15 menit).</p></div>
        <div class="rounded-2xl border border-slate-200 p-6"><div class="text-2xl">💳</div><h3 class="mt-2 text-lg font-bold">Sekali bayar</h3><p class="mt-1 text-sm text-slate-600">Bukan langganan bulanan. Satu kali beli, dipakai selama Swara masih kami operasikan.</p></div>
    </div>
</section>

@if ($lifetime)
<section class="bg-ts-cream/50">
    <div class="mx-auto max-w-3xl px-4 py-16 text-center">
        <h2 class="text-3xl font-bold text-ts-navy">Harga</h2>
        <div class="mx-auto mt-8 max-w-sm rounded-2xl border-2 border-ts-orange bg-white p-8 shadow-xl shadow-orange-100">
            <div class="font-bold">Lisensi Swara · Lifetime</div>
            <div class="mt-2 text-4xl font-extrabold text-ts-navy">{{ Fmt::rp($lifetime['price']) }}</div>
            @if (($lifetime['original'] ?? 0) > $lifetime['price'])
                <div class="mt-1 text-sm text-slate-400 line-through">{{ Fmt::rp($lifetime['original']) }}</div>
            @endif
            <p class="mt-2 text-sm text-slate-500">Sekali bayar · tanpa biaya bulanan</p>
            <a href="{{ url('/beli/swara/lifetime') }}" class="mt-6 block rounded-xl bg-ts-orange py-3 text-center text-sm font-semibold text-ts-navy hover:bg-ts-orange-dark">Beli lisensi</a>
        </div>
        <div class="mx-auto mt-8 max-w-xl rounded-2xl bg-white p-6 text-left text-sm text-slate-600 shadow-sm">
            <div class="font-bold text-ts-navy">Info pembayaran</div>
            <ul class="mt-2 list-disc space-y-1.5 pl-5">
                <li><b>Bayar lewat Midtrans.</b> Klik <i>Beli lisensi</i>, masuk dengan Google, lalu kamu diarahkan ke halaman pembayaran Midtrans dengan nominal sesuai harga di atas. Pilihan metode (QRIS, transfer virtual account, e-wallet) tampil di halaman itu.</li>
                <li>Lisensi aktif otomatis begitu pembayaran berhasil.</li>
                <li>Google Colab gratis dipakai untuk menjalankan mesin suaranya — tidak ada biaya tambahan dari kami untuk itu.</li>
            </ul>
        </div>
    </div>
</section>
@endif

<section class="mx-auto max-w-3xl px-4 py-16">
    <h2 class="text-center text-3xl font-bold text-ts-navy">Pertanyaan umum</h2>
    <div class="mt-8 divide-y divide-slate-100">
        @foreach ($faq as [$q, $a])
            <details class="group py-4"><summary class="flex cursor-pointer items-center justify-between font-semibold text-ts-navy"><span>{{ $q }}</span><svg class="chev size-4 shrink-0 text-slate-400 transition" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z"/></svg></summary><p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $a }}</p></details>
        @endforeach
    </div>
</section>

<footer class="border-t border-slate-100 py-8 text-center text-sm text-slate-400">© {{ date('Y') }} TemanSeller · <a href="{{ route('privacy') }}" class="hover:text-ts-orange-text">Privasi</a></footer>
</body>
</html>
