@php
    use App\Support\Fmt;
    $cta = url('/login');
    $faq = [
        ['Apa itu TemanSeller?', 'TemanSeller adalah kumpulan aplikasi untuk membantu seller online bekerja lebih cepat: riset produk, pantau kompetitor, sampai analisis dengan AI. Aplikasinya ditambah bertahap.'],
        ['Apakah satu akun bisa dipakai untuk semua aplikasi?', 'Bisa. Kamu cukup masuk dengan Google sekali. Di halaman akun kamu bisa lihat aplikasi apa saja yang sedang kamu pakai, kapan masa aktifnya, dan riwayat pembayarannya.'],
        ['Apakah langganan tiap aplikasi dipisah?', 'Ya. Setiap aplikasi punya masa trial, paket, dan harga sendiri. Berlangganan satu aplikasi tidak otomatis mengaktifkan aplikasi lain, jadi kamu hanya bayar yang kamu butuhkan.'],
        ['Bayarnya lewat apa?', 'Lewat Midtrans: QRIS, e-wallet, transfer bank virtual account, dan kartu. Status langganan aktif otomatis beberapa detik setelah pembayaran berhasil.'],
        ['Ada trial gratis?', 'Ada. Tiap aplikasi punya trial gratis sendiri tanpa kartu kredit. Lama trial tertera di kartu aplikasinya.'],
        ['Kapan aplikasi berikutnya rilis?', 'Sedang dikerjakan satu per satu. Begitu rilis, aplikasinya langsung muncul di halaman ini dan di akunmu.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="apple-touch-icon" href="{{ asset('img/brand/temanseller-180.png') }}">
    <title>TemanSeller — Aplikasi Pendukung Seller Marketplace</title>
    <meta name="description" content="TemanSeller menyediakan aplikasi untuk membantu seller online: riset produk, pantau kompetitor, dan analisis AI. Satu akun untuk semua aplikasi, trial gratis.">
    <meta property="og:title" content="TemanSeller — Aplikasi Pendukung Seller Marketplace">
    <meta property="og:description" content="Satu akun, banyak aplikasi untuk seller online. Mulai dari Shofee, extension Chrome untuk riset Shopee.">
    @vite(['resources/css/app.css'])
    <style>
        body{font-family:'Outfit',system-ui,sans-serif}
        .grad{background:radial-gradient(1100px 480px at 85% -10%,#ffe3d9 0,transparent 60%),radial-gradient(800px 380px at -10% 10%,#fff1ea 0,transparent 55%),#fffaf7}
        summary::-webkit-details-marker{display:none}
        details[open] .chev{transform:rotate(180deg)}
    </style>
</head>
<body class="bg-white text-slate-800 antialiased">

<header class="sticky top-0 z-50 border-b border-orange-100/70 bg-white/85 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5">
        <x-brand :href="url('/')" size="size-12" class="text-lg" />
        <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
            <a href="#aplikasi" class="hover:text-[#ee4d2d]">Aplikasi</a><a href="#kenapa" class="hover:text-[#ee4d2d]">Kenapa TemanSeller</a><a href="#cara" class="hover:text-[#ee4d2d]">Cara mulai</a><a href="#faq" class="hover:text-[#ee4d2d]">FAQ</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="{{ $cta }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:text-[#ee4d2d] sm:block">Masuk</a>
            <a href="#aplikasi" class="rounded-lg bg-[#ee4d2d] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#d7401f]">Lihat aplikasi</a>
        </div>
    </div>
</header>

<section class="grad">
    <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 pb-16 pt-14 lg:grid-cols-2 lg:pb-24 lg:pt-20">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white px-3 py-1 text-xs font-semibold text-[#ee4d2d]">🛠️ Alat kerja seller marketplace</span>
            <h1 class="mt-5 text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">Jualan online makin ringan, <span class="text-[#ee4d2d]">ditemani aplikasi yang pas.</span></h1>
            <p class="mt-5 max-w-xl text-lg text-slate-600">Riset produk, intip kompetitor, sampai analisis AI. TemanSeller mengumpulkan aplikasi pendukung seller dalam satu akun, dan terus bertambah.</p>
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="#aplikasi" class="rounded-xl bg-[#ee4d2d] px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-orange-200 hover:bg-[#d7401f]">Lihat semua aplikasi →</a>
                <a href="{{ $cta }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-base font-semibold text-slate-700 hover:border-[#ee4d2d] hover:text-[#ee4d2d]">Masuk ke akun</a>
            </div>
            <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-500"><li>✓ Daftar pakai Google</li><li>✓ Trial gratis tanpa kartu kredit</li><li>✓ Bayar QRIS &amp; e-wallet</li></ul>
        </div>
        <div class="relative">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-orange-100">
                <img src="{{ asset('img/preview/toko-ringkasan.jpg') }}" alt="Shofee: ringkasan toko di halaman Shopee" class="w-full">
                <img src="{{ asset('img/preview/toko-analisa-ai.jpg') }}" alt="Shofee: analisis AI" class="w-full border-t border-slate-100">
            </div>
            <div class="absolute -bottom-4 -left-2 flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-lg ring-1 ring-slate-100 sm:-left-6"><img src="{{ asset('img/brand/shofee-64.png') }}" alt="" class="size-6 rounded-md">Shofee <span class="font-normal text-slate-500">· aplikasi pertama</span></div>
        </div>
    </div>
</section>

<section class="border-y border-slate-100 bg-white">
    <div class="mx-auto grid max-w-6xl gap-4 px-4 py-6 text-center text-sm font-medium text-slate-600 sm:grid-cols-3">
        <div>🔑 Satu akun untuk semua aplikasi</div><div>💳 QRIS, e-wallet, dan virtual account</div><div>🎁 Trial gratis di setiap aplikasi</div>
    </div>
</section>

<section id="aplikasi" class="py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Pilih yang kamu butuhin hari ini</h2>
            <p class="mt-3 text-slate-600">Setiap aplikasi punya trial dan langganan sendiri, jadi bayar yang dipakai saja.</p>
        </div>
        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($apps as $x)
                @php $a = $x['app']; @endphp
                <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <x-app-icon :app="$a" size="size-14" />
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $a->slug === 'shofee' ? 'Extension Chrome' : 'Aplikasi' }}</span>
                    </div>
                    <h3 class="mt-4 text-xl font-bold text-slate-900">{{ $a->name }}</h3>
                    @if ($a->tagline)<p class="mt-1 text-sm font-medium text-[#ee4d2d]">{{ $a->tagline }}</p>@endif
                    @if ($a->slug === 'shofee')<p class="mt-3 text-sm text-slate-600">Lihat estimasi omzet kompetitor, produk andalan, dan peluang pasar langsung di halaman Shopee, lengkap dengan rekomendasi dari AI.</p>@endif
                    <div class="mt-5 flex items-end justify-between border-t border-slate-100 pt-4">
                        <div>@if ($x['from'])<div class="text-xs text-slate-500">mulai</div><div class="text-lg font-bold text-slate-900">{{ Fmt::rp($x['from']) }}<span class="text-sm font-normal text-slate-500">/bulan</span></div>@endif</div>
                        @if ($x['trial'] > 0)<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Trial {{ $x['trial'] }} hari</span>@endif
                    </div>
                    <div class="mt-5 flex gap-2">
                        <a href="{{ $x['url'] }}" class="flex-1 rounded-xl bg-[#ee4d2d] px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-[#d7401f]">Lihat detail</a>
                        <a href="{{ $a->slug === 'shofee' ? $cta : url('/akun/aplikasi/' . $a->slug) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-[#ee4d2d] hover:text-[#ee4d2d]">Coba gratis</a>
                    </div>
                </article>
            @endforeach
            <article class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-orange-200 bg-orange-50/40 p-6 text-center">
                <span class="grid size-12 place-items-center rounded-xl bg-white text-2xl shadow-sm">🚀</span>
                <h3 class="mt-4 text-lg font-bold text-slate-900">Aplikasi berikutnya</h3>
                <p class="mt-2 max-w-xs text-sm text-slate-600">Sedang dikerjakan. Aplikasi baru akan muncul di sini dan otomatis tersedia di akunmu.</p>
                <span class="mt-4 rounded-full bg-white px-3 py-1 text-xs font-semibold text-[#ee4d2d] ring-1 ring-orange-200">Segera hadir</span>
            </article>
        </div>
    </div>
</section>

<section id="kenapa" class="bg-slate-50 py-20">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="mx-auto max-w-2xl text-center text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Kenapa TemanSeller</h2>
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['🔑', 'Satu akun, semua aplikasi', 'Masuk dengan Google sekali. Semua aplikasi dan langgananmu ada di satu halaman akun.'], ['🧾', 'Langganan terpisah', 'Tiap aplikasi punya trial dan paket sendiri. Tidak ada paket gabungan yang bikin bayar fitur yang tak terpakai.'], ['💳', 'Bayar cara lokal', 'QRIS, e-wallet, dan virtual account lewat Midtrans. Aktif otomatis setelah bayar.'], ['🤖', 'Dibantu AI', 'Data dirangkum jadi temuan, peluang, dan saran aksi yang bisa langsung dikerjakan.']] as [$i, $t, $d])
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100"><div class="text-3xl">{{ $i }}</div><h3 class="mt-3 font-bold text-slate-900">{{ $t }}</h3><p class="mt-2 text-sm text-slate-600">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>

<section id="cara" class="bg-slate-900 py-20 text-white">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="text-center text-3xl font-extrabold tracking-tight sm:text-4xl">Cara mulai</h2>
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['01', 'Pilih aplikasi', 'Lihat daftar aplikasi dan buka detailnya.'], ['02', 'Masuk dengan Google', 'Tanpa kartu kredit. Trial langsung aktif.'], ['03', 'Pasang & pakai', 'Ikuti petunjuk di halaman aplikasi, mulai dari memasang extension.'], ['04', 'Berlangganan', 'Bayar lewat QRIS atau e-wallet kalau trial sudah terasa manfaatnya.']] as [$n, $t, $d])
                <div class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10"><div class="text-sm font-bold text-orange-400">{{ $n }}</div><h3 class="mt-2 font-bold">{{ $t }}</h3><p class="mt-2 text-sm text-slate-300">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>

<section id="faq" class="bg-slate-50 py-20">
    <div class="mx-auto max-w-3xl px-4">
        <h2 class="text-center text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Pertanyaan umum</h2>
        <div class="mt-10 space-y-3">
            @foreach ($faq as [$q, $a])
                <details class="group rounded-xl bg-white p-5 ring-1 ring-slate-100"><summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold text-slate-900">{{ $q }}<span class="chev text-slate-400 transition">⌄</span></summary><p class="mt-3 text-sm text-slate-600">{{ $a }}</p></details>
            @endforeach
        </div>
    </div>
</section>

<section class="px-4 py-16">
    <div class="mx-auto max-w-5xl rounded-3xl bg-gradient-to-br from-[#ee4d2d] to-[#ff7a45] px-6 py-14 text-center text-white">
        <h2 class="text-3xl font-extrabold sm:text-4xl">Jualan lebih ringan mulai hari ini</h2>
        <p class="mx-auto mt-3 max-w-xl text-white/90">Coba aplikasi pertama kami gratis, tanpa kartu kredit.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="#aplikasi" class="rounded-xl bg-white px-7 py-3.5 font-semibold text-[#ee4d2d] shadow hover:bg-orange-50">Lihat aplikasi</a>
            <a href="{{ $cta }}" class="rounded-xl border border-white/60 px-7 py-3.5 font-semibold text-white hover:bg-white/10">Masuk ke akun</a>
        </div>
    </div>
</section>

<footer class="border-t border-slate-100 bg-white">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-slate-500 sm:flex-row">
        <x-brand size="size-10" class="font-semibold text-slate-700" />
        <div class="flex flex-wrap justify-center gap-5"><a href="#aplikasi" class="hover:text-[#ee4d2d]">Aplikasi</a><a href="#faq" class="hover:text-[#ee4d2d]">FAQ</a><a href="{{ $cta }}" class="hover:text-[#ee4d2d]">Akun</a><a href="{{ url('/privasi') }}" class="hover:text-[#ee4d2d]">Privasi</a>@foreach ($apps as $x)<a href="{{ $x['url'] }}" class="hover:text-[#ee4d2d]">{{ $x['app']->name }}</a>@endforeach</div>
        <div>© {{ date('Y') }} TemanSeller. <span class="text-xs text-slate-400">v{{ config('spill.version') }}</span></div>
    </div>
</footer>
</body>
</html>
