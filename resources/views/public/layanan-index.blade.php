@php
    $login = url('/login');
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="apple-touch-icon" href="{{ asset('img/brand/temanseller-180.png') }}">
    <title>Layanan — TemanSeller</title>
    <meta name="description" content="Layanan TemanSeller untuk seller online: kelola sosial media Instagram &amp; Threads, dan layanan lain yang segera hadir.">
    <meta property="og:title" content="Layanan — TemanSeller">
    <meta property="og:description" content="Kelola sosial media tokomu bersama TemanSeller.">
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
        <x-brand :href="$hubUrl" size="size-12" class="text-lg" />
        <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex"><a href="{{ $hubUrl }}" class="hover:text-[#ee4d2d]">Beranda</a><a href="#layanan" class="hover:text-[#ee4d2d]">Layanan</a></nav>
        <div class="flex items-center gap-2"><a href="{{ $login }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:text-[#ee4d2d] sm:block">Masuk</a><a href="{{ route('layanan.sosmed') }}#paket" class="rounded-lg bg-[#ee4d2d] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#d7401f]">Lihat paket</a></div>
    </div>
</header>

<section class="grad">
    <div class="mx-auto max-w-4xl px-4 pb-8 pt-10 text-center lg:pb-10 lg:pt-14">
        <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white px-3 py-1 text-xs font-semibold text-[#ee4d2d]">🧰 Layanan TemanSeller</span>
        <h1 class="mt-4 text-3xl font-extrabold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">Dibantu tim, <span class="text-[#ee4d2d]">tokomu makin fokus jualan.</span></h1>
        <p class="mx-auto mt-4 max-w-2xl text-base text-slate-600 sm:text-lg">Pilih layanan yang kamu butuhkan. Layanan baru akan terus ditambah.</p>
    </div>
</section>

<section id="layanan" class="pb-16 pt-4">
    <div class="mx-auto grid max-w-5xl gap-5 px-4 sm:grid-cols-2">
        <a href="{{ route('layanan.sosmed') }}" class="group flex flex-col rounded-2xl border border-orange-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Tersedia</span>
            <h2 class="mt-3 text-xl font-bold text-slate-900">Kelola Social Media</h2>
            <p class="mt-2 flex-1 text-sm text-slate-600">Instagram &amp; Threads: ide, desain, caption, dan jadwal posting. Pilih konten siap pakai atau dibantu posting.</p>
            <span class="mt-4 font-semibold text-[#ee4d2d]">Lihat paket &amp; harga →</span>
        </a>
        @foreach ($soon as $x)
            <div class="flex flex-col rounded-2xl border border-dashed border-violet-300 bg-violet-50/50 p-6">
                <span class="inline-flex w-fit rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">🚀 Segera hadir</span>
                <h2 class="mt-3 text-xl font-bold text-slate-800">{{ $x }}</h2>
                <p class="mt-2 flex-1 text-sm text-slate-500">Sedang kami siapkan. @if ($wa)Mau diberi kabar saat sudah tersedia? Hubungi kami lewat WhatsApp.@endif</p>
                @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="mt-4 font-semibold text-violet-700 hover:underline">Hubungi via WhatsApp →</a>@endif
            </div>
        @endforeach
    </div>
</section>

<footer class="border-t border-slate-100 bg-white">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-slate-500 sm:flex-row">
        <x-brand size="size-10" class="font-semibold text-slate-700" />
        <div class="flex flex-wrap justify-center gap-5"><a href="{{ $hubUrl }}" class="hover:text-[#ee4d2d]">Beranda</a><a href="{{ $login }}" class="hover:text-[#ee4d2d]">Akun</a><a href="{{ url('/privasi') }}" class="hover:text-[#ee4d2d]">Privasi</a></div>
        <div>© {{ date('Y') }} TemanSeller. <span class="text-xs text-slate-400">v{{ config('temanseller.version') }}</span></div>
    </div>
</footer>
</body>
</html>
