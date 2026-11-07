@php
    use App\Support\Fmt;
    $cta = url('/login');
    $layananTypes = $layananTypes ?? collect(); $layananSoon = $layananSoon ?? [];
    $layananOn = $layananOn ?? false; $soonAll = $soonAll ?? []; $layananFrom = $layananFrom ?? null;
    $slides = [];
    foreach ($apps as $x) {
        $a = $x['app'];
        $img = is_file(public_path('img/slides/' . $a->slug . '.webp')) ? 'img/slides/' . $a->slug . '.webp' : null;
        $slides[] = ['badge' => $a->slug === 'shofee' ? 'Extension Chrome' : ($a->slug === 'audit' ? 'Web' : 'Aplikasi'), 'kind' => $a->slug === 'audit' ? 'Aplikasi web' : 'Aplikasi untuk seller', 'title' => $a->name,
            'desc' => $a->slug === 'shofee' ? 'Riset produk dan intip kompetitor langsung di halaman Shopee, lengkap dengan analisis AI.' : ($a->slug === 'audit' ? 'Periksa kesehatan toko dan produk Shopee-mu: skor, temuan, dan langkah perbaikan dari AI.' : ($a->tagline ?: 'Aplikasi pendukung seller.')),
            'url' => $x['url'], 'cta' => 'Lihat detail', 'img' => $img, 'alt' => 'Maskot TemanSeller untuk ' . $a->name, 'soon' => false];
    }
    if ($layananOn) {
        $slides[] = ['badge' => 'Layanan', 'kind' => 'Berlangganan', 'title' => 'Kelola Social Media', 'desc' => 'Konten Instagram & Threads dikerjakan tim kami: ide, desain, caption, dan jadwal posting.',
            'url' => route('layanan.sosmed'), 'cta' => 'Lihat paket', 'img' => 'img/slides/sosmed.webp', 'alt' => 'Maskot TemanSeller dengan ponsel berisi konten sosial media', 'soon' => false];
        foreach ($soonAll ?? [] as $n) {
            $k = str_contains(mb_strtolower($n), 'tiktok') ? 'tiktok' : (str_contains(mb_strtolower($n), 'live') ? 'live' : null);
            $slides[] = ['badge' => '', 'kind' => '', 'title' => $n, 'img' => $k ? 'img/slides/' . $k . '.webp' : null, 'alt' => 'Ilustrasi ' . $n, 'url' => null, 'cta' => '', 'soon' => true,
                'desc' => $k === 'tiktok' ? 'Video pendek untuk tokomu, dari ide sampai jadwal tayang. Sedang kami siapkan.' : ($k === 'live' ? 'Host live jualan untuk tokomu di marketplace. Sedang kami siapkan.' : 'Sedang kami siapkan.')];
        }
    }
    $faq = [
        ['Apa itu TemanSeller?', 'TemanSeller adalah kumpulan aplikasi untuk membantu seller online bekerja lebih cepat: riset produk, pantau kompetitor, sampai analisis dengan AI. Aplikasinya ditambah bertahap.'],
        ['Apakah satu akun bisa dipakai untuk semua aplikasi?', 'Bisa. Kamu cukup masuk dengan Google sekali. Di halaman akunmu bisa lihat aplikasi apa saja yang sedang kamu pakai, kapan masa aktifnya, dan riwayat pembayarannya.'],
        ['Apakah langganan tiap aplikasi dipisah?', 'Ya. Setiap aplikasi punya masa trial, paket, dan harga sendiri. Berlangganan satu aplikasi tidak otomatis mengaktifkan aplikasi lain, jadi kamu hanya bayar yang kamu butuhkan.'],
        ['Bayarnya lewat apa?', 'Lewat Midtrans: QRIS, e-wallet, transfer bank virtual account, dan kartu. Status langganan aktif otomatis beberapa detik setelah pembayaran berhasil.'],
        ['Ada trial gratis?', 'Ada. Tiap aplikasi punya trial gratis sendiri tanpa kartu kredit. Lama trial tertera di kartu aplikasinya.'],
        ['Apa itu layanan Kelola Instagram & Threads?', 'Layanan berlangganan di mana tim kami yang menyiapkan dan mengelola konten Instagram dan Threads tokomu: ide, desain, caption, sampai jadwal posting. Kamu cukup memilih paket, lalu hasil kerja tersimpan rapi di folder Drive yang bisa kamu buka dari halaman akun.'],
        ['Shofee itu aplikasi apa?', 'Shofee adalah extension Chrome. Kamu memasangnya dari Chrome Web Store di browser komputer, lalu fiturnya muncul langsung di halaman Shopee. Bukan aplikasi HP.'],
        ['Kapan aplikasi berikutnya rilis?', 'Sedang dikerjakan satu per satu. Begitu rilis, aplikasinya langsung muncul di halaman ini dan di akunmu.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="facebook-domain-verification" content="gzts236t5urmz20vp3wahb33svsly8" />
    <link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="apple-touch-icon" href="{{ asset('img/brand/temanseller-180.png') }}">
    <title>TemanSeller — Aplikasi Pendukung Seller Marketplace</title>
    <meta name="description" content="TemanSeller menyediakan aplikasi untuk membantu seller online: riset produk, pantau kompetitor, dan analisis AI. Satu akun untuk semua aplikasi, trial gratis. Ada juga layanan kelola Instagram & Threads.">
    <meta property="og:title" content="TemanSeller — Aplikasi Pendukung Seller Marketplace">
    <meta property="og:description" content="Satu akun, banyak aplikasi untuk seller online. Mulai dari Shofee, extension Chrome untuk riset Shopee.">
    @vite(['resources/css/app.css'])
    <style>
        body{font-family:'Outfit',system-ui,sans-serif}
        .grad{background:radial-gradient(1100px 480px at 85% -10%,#FFE8C7 0,transparent 60%),radial-gradient(800px 380px at -10% 10%,#FFF1DC 0,transparent 55%),#FFFAF2}
        summary::-webkit-details-marker{display:none}
        details[open] .chev{transform:rotate(180deg)}
    </style>
</head>
<body class="bg-white text-slate-800 antialiased">

<header class="sticky top-0 z-50 border-b border-orange-100/70 bg-white/85 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5">
        <x-brand :href="url('/')" size="size-12" class="text-lg" />
        <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
            <div class="group relative">
                <button type="button" class="flex items-center gap-1 hover:text-ts-orange-text focus:outline-none" aria-haspopup="true">Aplikasi <svg class="size-3.5 transition group-hover:rotate-180 group-focus-within:rotate-180" viewBox="0 0 20 20" fill="currentColor"><path d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4z"/></svg></button>
                <div class="invisible absolute left-1/2 top-full z-50 w-72 -translate-x-1/2 pt-3 opacity-0 transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                    <div class="rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        @foreach ($apps as $x)
                            <a href="{{ $x['url'] }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-orange-50"><x-app-icon :app="$x['app']" size="size-9" /><span class="min-w-0"><span class="block truncate font-semibold text-slate-800">{{ $x['app']->name }}</span>@if ($x['app']->tagline)<span class="block truncate text-xs font-normal text-slate-500">{{ $x['app']->tagline }}</span>@endif</span></a>
                        @endforeach
                        <a href="#aplikasi" class="mt-1 block rounded-lg border-t border-slate-100 px-3 py-2.5 text-center text-ts-orange-text hover:bg-orange-50">Lihat semua aplikasi →</a>
                    </div>
                </div>
            </div>
            @if ($layananOn)
            <div class="group relative">
                <button type="button" class="flex items-center gap-1 hover:text-ts-orange-text focus:outline-none" aria-haspopup="true">Layanan <svg class="size-3.5 transition group-hover:rotate-180 group-focus-within:rotate-180" viewBox="0 0 20 20" fill="currentColor"><path d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4z"/></svg></button>
                <div class="invisible absolute left-1/2 top-full z-50 w-72 -translate-x-1/2 pt-3 opacity-0 transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                    <div class="rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <a href="{{ route('layanan.sosmed') }}" class="block rounded-lg px-3 py-2.5 hover:bg-orange-50"><span class="block font-semibold text-slate-800">Kelola Social Media</span><span class="block text-xs font-normal text-slate-500">Instagram &amp; Threads: konten siap pakai atau dibantu posting</span></a>
                        @foreach ($layananSoon as $x)
                            <div class="flex items-center justify-between gap-2 rounded-lg px-3 py-2.5 opacity-70"><span class="font-semibold text-slate-600">{{ $x }}</span><span class="shrink-0 whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">Segera hadir</span></div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            <a href="#kenapa" class="hover:text-ts-orange-text">Kenapa TemanSeller</a><a href="#cara" class="hover:text-ts-orange-text">Cara mulai</a><a href="#faq" class="hover:text-ts-orange-text">FAQ</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="{{ $cta }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:text-ts-orange-text sm:block">Masuk</a>
            <a href="#aplikasi" class="rounded-lg bg-ts-orange px-4 py-2.5 text-sm font-semibold text-ts-navy shadow-sm hover:bg-ts-orange-dark">Lihat aplikasi</a>
        </div>
    </div>
</header>

<section class="grad">
    <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 pb-16 pt-14 lg:grid-cols-2 lg:pb-24 lg:pt-20">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white px-3 py-1 text-xs font-semibold text-ts-orange-text">☕ Aplikasi &amp; layanan untuk Juragan marketplace</span>
            <h1 class="mt-5 text-4xl font-extrabold leading-[1.1] tracking-tight text-ts-navy sm:text-5xl">Jualan online makin ringan, <span class="text-ts-orange-text">ditemani aplikasi yang pas.</span></h1>
            <p class="mt-5 max-w-xl text-lg text-slate-600">Halo, Juragan. Riset produk, intip kompetitor, audit toko, sampai konten Instagram &amp; Threads yang dikelola untukmu, semua ada di satu akun. Pelan-pelan, satu langkah kecil dulu.</p>
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="#aplikasi" class="rounded-xl bg-ts-orange px-6 py-3.5 text-base font-semibold text-ts-navy shadow-lg shadow-orange-200 hover:bg-ts-orange-dark">Lihat semua aplikasi →</a>
                @if ($layananOn)<a href="{{ route('layanan.sosmed') }}" class="rounded-xl border border-orange-200 bg-orange-50 px-6 py-3.5 text-base font-semibold text-ts-orange-text hover:bg-orange-100">Kelola Social Media</a>@endif
                <a href="{{ $cta }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-base font-semibold text-slate-700 hover:border-ts-orange hover:text-ts-orange-text">Masuk ke akun</a>
            </div>
            <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-500"><li>✓ Daftar pakai Google</li><li>✓ Trial gratis tanpa kartu kredit</li><li>✓ Bayar QRIS &amp; e-wallet</li></ul>
        </div>
        <div class="relative" id="slideshow" role="region" aria-roledescription="carousel" aria-label="Ringkasan aplikasi dan layanan TemanSeller">
            <div class="overflow-hidden rounded-3xl border border-orange-100 bg-white shadow-2xl shadow-orange-100">
                <div class="relative">
                    @foreach ($slides as $i => $s)
                        <article data-slide="{{ $i }}" class="slide {{ $i === 0 ? '' : 'hidden' }}" aria-roledescription="slide" aria-label="{{ $i + 1 }} dari {{ count($slides) }}">
                            <div class="aspect-[4/3] w-full bg-ts-cream">
                                @if ($s['img'])<img src="{{ asset($s['img']) }}" alt="{{ $s['alt'] }}" class="size-full object-cover" @if ($i > 0) loading="lazy" @endif>@else<div class="grid size-full place-items-center"><img src="{{ asset('img/brand/temanseller-logo.png') }}" alt="" class="h-3/5 object-contain"></div>@endif
                            </div>
                            <div class="p-6">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $s['soon'] ? 'bg-violet-100 text-violet-700' : 'bg-orange-100 text-ts-orange-text' }}">{{ $s['soon'] ? '🚀 Segera hadir' : $s['badge'] }}</span>
                                    @if (!$s['soon'])<span class="text-xs font-medium text-slate-400">{{ $s['kind'] }}</span>@endif
                                </div>
                                <h3 class="mt-3 text-xl font-bold text-ts-navy">{{ $s['title'] }}</h3>
                                <p class="mt-1.5 text-sm text-slate-600">{{ $s['desc'] }}</p>
                                @if ($s['url'])<a href="{{ $s['url'] }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-ts-orange-text hover:underline">{{ $s['cta'] }} →</a>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
            @if (count($slides) > 1)
            <div class="mt-4 flex items-center justify-center gap-3">
                <button type="button" id="sl-prev" aria-label="Sebelumnya" class="grid size-9 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 hover:border-ts-orange hover:text-ts-orange-text">‹</button>
                <div class="flex items-center gap-2" id="sl-dots">@foreach ($slides as $i => $s)<button type="button" data-dot="{{ $i }}" aria-label="Slide {{ $i + 1 }}: {{ $s['title'] }}" class="h-2 rounded-full transition-all {{ $i === 0 ? 'w-6 bg-ts-orange' : 'w-2 bg-slate-300' }}"></button>@endforeach</div>
                <button type="button" id="sl-next" aria-label="Berikutnya" class="grid size-9 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 hover:border-ts-orange hover:text-ts-orange-text">›</button>
            </div>
            @endif
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
            <h2 class="text-3xl font-extrabold tracking-tight text-ts-navy sm:text-4xl">Pilih yang kamu butuhkan hari ini</h2>
            <p class="mt-3 text-slate-600">Setiap aplikasi punya trial dan langganan sendiri, jadi bayar yang dipakai saja.</p>
        </div>
        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($apps as $x)
                @php $a = $x['app']; @endphp
                <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <x-app-icon :app="$a" size="size-14" />
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $a->slug === 'shofee' ? 'Extension Chrome' : ($a->slug === 'audit' ? 'Web' : 'Aplikasi') }}</span>
                    </div>
                    <h3 class="mt-4 text-xl font-bold text-ts-navy">{{ $a->name }}</h3>
                    @if ($a->tagline)<p class="mt-1 text-sm font-medium text-ts-orange-text">{{ $a->tagline }}</p>@endif
                    @if ($a->slug === 'shofee')<p class="mt-3 text-sm text-slate-600">Extension Chrome: pasang di browser komputer, lalu lihat estimasi omzet kompetitor, produk andalan, dan peluang pasar langsung di halaman Shopee, lengkap dengan rekomendasi dari AI.</p>@endif
                    @if ($a->slug === 'audit')<p class="mt-3 text-sm text-slate-600">Unggah screenshot atau PDF halaman produk/toko Shopee, dapatkan skor audit dan rekomendasi AI. Berkas tidak disimpan dan tanpa akses ke akun Shopee-mu.</p>@endif
                    <div class="mt-5 flex items-end justify-between border-t border-slate-100 pt-4">
                        <div>@php $mp = $x['plans']['monthly'] ?? null; $price = (int) ($mp['price'] ?? 0) > 0 ? (int) $mp['price'] : $x['from']; $orig = (int) ($mp['original'] ?? 0); @endphp
                            @if ($price)<div class="text-xs text-slate-500">mulai</div>@if ($orig > $price)<div class="flex items-center gap-2"><span class="text-sm text-slate-400 line-through">{{ Fmt::rp($orig) }}</span><span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Hemat {{ (int) round((1 - $price / $orig) * 100) }}%</span></div>@endif<div class="text-lg font-bold text-ts-navy">{{ Fmt::rp($price) }}<span class="text-sm font-normal text-slate-500">/bulan</span></div>@endif</div>
                        @if ($x['trial'] > 0)<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Trial {{ $x['trial'] }} hari</span>@elseif ($a->slug === 'audit')<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ \App\Services\Audit::freeScans() }} scan gratis/bulan</span>@endif
                    </div>
                    <div class="mt-5 flex gap-2">
                        <a href="{{ $x['url'] }}" class="flex-1 whitespace-nowrap rounded-xl bg-ts-orange px-3 py-2.5 text-center text-sm font-semibold text-ts-navy hover:bg-ts-orange-dark">Lihat detail</a>
                        @if ($a->download_url)<a href="{{ $a->download_url }}" target="_blank" rel="noopener" class="whitespace-nowrap rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:border-ts-orange hover:text-ts-orange-text">Pasang ↗</a>@endif
                        <a href="{{ $a->slug === 'shofee' ? $cta : ($a->slug === 'audit' ? route('audit') : url('/akun/aplikasi/' . $a->slug)) }}" class="whitespace-nowrap rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:border-ts-orange hover:text-ts-orange-text">Coba gratis</a>
                    </div>
                </article>
            @endforeach
            <article class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-orange-200 bg-orange-50/40 p-6 text-center">
                <span class="grid size-12 place-items-center rounded-xl bg-white text-2xl shadow-sm">🚀</span>
                <h3 class="mt-4 text-lg font-bold text-ts-navy">Aplikasi berikutnya</h3>
                <p class="mt-2 max-w-xs text-sm text-slate-600">Sedang dikerjakan. Aplikasi baru akan muncul di sini dan otomatis tersedia di akunmu.</p>
                <span class="mt-4 rounded-full bg-white px-3 py-1 text-xs font-semibold text-ts-orange-text ring-1 ring-orange-200">Segera hadir</span>
            </article>
        </div>
    </div>
</section>

@if ($layananOn)
<section id="layanan" class="bg-gradient-to-b from-white to-orange-50/60 py-20">
    <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 lg:grid-cols-2">
        <div>
            <span class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-ts-orange-text">Layanan berlangganan</span>
            <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-ts-navy sm:text-4xl">Kelola Instagram &amp; Threads tokomu, kami yang kerjakan</h2>
            <p class="mt-4 text-slate-600">Dari ide konten, desain, caption, sampai jadwal posting. Kamu fokus jualan, akun sosmed tetap hidup. Hasil kerja tersimpan di folder Drive yang bisa kamu buka dari halaman akun.</p>
            <ul class="mt-5 space-y-2 text-sm text-slate-700"><li>✓ Pilih paket sesuai kebutuhan, harga jelas</li><li>✓ Lihat status langganan &amp; folder Drive di akunmu</li><li>✓ Ada portofolio hasil kerja sebelumnya</li></ul>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('layanan.sosmed') }}" class="rounded-xl bg-ts-orange px-6 py-3.5 font-semibold text-ts-navy shadow-lg shadow-orange-200 hover:bg-ts-orange-dark">Lihat paket &amp; portofolio →</a>
                @if ($layananFrom)<span class="self-center text-sm text-slate-500">mulai <b class="text-ts-navy">{{ Fmt::rp($layananFrom) }}</b></span>@endif
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            @foreach ([['📸', 'Instagram', 'Feed, carousel, dan story terjadwal'], ['🧵', 'Threads', 'Posting rutin & balasan yang terarah'], ['🎨', 'Visual konsisten', 'Gaya brand yang seragam di semua konten'], ['📁', 'Hasil di Drive', 'Semua aset rapi dan bisa kamu unduh']] as [$i, $t, $d])
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-orange-100"><div class="text-3xl">{{ $i }}</div><h3 class="mt-2 font-bold text-ts-navy">{{ $t }}</h3><p class="mt-1 text-sm text-slate-600">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section id="kenapa" class="bg-slate-50 py-20">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="mx-auto max-w-2xl text-center text-3xl font-extrabold tracking-tight text-ts-navy sm:text-4xl">Kenapa TemanSeller</h2>
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['🔑', 'Satu akun, semua aplikasi', 'Masuk dengan Google sekali. Semua aplikasi dan langgananmu ada di satu halaman akun.'], ['🧾', 'Langganan terpisah', 'Tiap aplikasi punya trial dan paket sendiri. Tidak ada paket gabungan yang bikin bayar fitur yang tak terpakai.'], ['💳', 'Bayar cara lokal', 'QRIS, e-wallet, dan virtual account lewat Midtrans. Aktif otomatis setelah bayar.'], ['🤖', 'Dibantu AI', 'Data dirangkum jadi temuan, peluang, dan saran aksi yang bisa langsung dikerjakan.']] as [$i, $t, $d])
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100"><div class="text-3xl">{{ $i }}</div><h3 class="mt-3 font-bold text-ts-navy">{{ $t }}</h3><p class="mt-2 text-sm text-slate-600">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>

<section id="cara" class="bg-ts-navy py-20 text-white">
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
        <h2 class="text-center text-3xl font-extrabold tracking-tight text-ts-navy sm:text-4xl">Pertanyaan umum</h2>
        <div class="mt-10 space-y-3">
            @foreach ($faq as [$q, $a])
                <details class="group rounded-xl bg-white p-5 ring-1 ring-slate-100"><summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold text-ts-navy">{{ $q }}<span class="chev text-slate-400 transition">⌄</span></summary><p class="mt-3 text-sm text-slate-600">{{ $a }}</p></details>
            @endforeach
        </div>
    </div>
</section>

<section class="px-4 py-16">
    <div class="mx-auto max-w-5xl rounded-3xl bg-gradient-to-br from-ts-orange to-ts-gold px-6 py-14 text-center text-ts-navy">
        <h2 class="text-3xl font-extrabold sm:text-4xl">Jualan lebih ringan mulai hari ini</h2>
        <p class="mx-auto mt-3 max-w-xl text-ts-navy/80">Coba aplikasi pertama kami gratis, tanpa kartu kredit.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="#aplikasi" class="rounded-xl bg-ts-navy px-7 py-3.5 font-semibold text-white shadow hover:bg-[#10244d]">Lihat aplikasi</a>
            <a href="{{ $cta }}" class="rounded-xl border border-ts-navy/40 px-7 py-3.5 font-semibold text-ts-navy hover:bg-white/40">Masuk ke akun</a>
        </div>
    </div>
</section>

<footer class="border-t border-slate-100 bg-white">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-slate-500 sm:flex-row">
        <x-brand size="size-10" class="font-semibold text-slate-700" />
        <div class="flex flex-wrap justify-center gap-5"><a href="#aplikasi" class="hover:text-ts-orange-text">Aplikasi</a>@if ($layananOn)<a href="{{ route('layanan.sosmed') }}" class="hover:text-ts-orange-text">Layanan</a>@endif<a href="#faq" class="hover:text-ts-orange-text">FAQ</a><a href="{{ $cta }}" class="hover:text-ts-orange-text">Akun</a><a href="{{ url('/privasi') }}" class="hover:text-ts-orange-text">Privasi</a>@foreach ($apps as $x)<a href="{{ $x['url'] }}" class="hover:text-ts-orange-text">{{ $x['app']->name }}</a>@endforeach</div>
        <div>© {{ date('Y') }} TemanSeller. <span class="text-xs text-slate-400">v{{ config('temanseller.version') }}</span></div>
    </div>
</footer>
<script>
(() => {
    const root = document.getElementById('slideshow'); if (!root) return;
    const slides = [...root.querySelectorAll('.slide')], dots = [...root.querySelectorAll('[data-dot]')]; if (slides.length < 2) return;
    let i = 0, timer = null; const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const show = (n) => { i = (n + slides.length) % slides.length; slides.forEach((s, k) => s.classList.toggle('hidden', k !== i)); dots.forEach((d, k) => { d.className = 'h-2 rounded-full transition-all ' + (k === i ? 'w-6 bg-ts-orange' : 'w-2 bg-slate-300'); }); };
    const play = () => { if (reduce) return; stop(); timer = setInterval(() => show(i + 1), 5500); }, stop = () => { if (timer) clearInterval(timer); timer = null; };
    document.getElementById('sl-prev')?.addEventListener('click', () => { show(i - 1); play(); });
    document.getElementById('sl-next')?.addEventListener('click', () => { show(i + 1); play(); });
    dots.forEach((d) => d.addEventListener('click', () => { show(+d.dataset.dot); play(); }));
    root.addEventListener('mouseenter', stop); root.addEventListener('mouseleave', play); root.addEventListener('focusin', stop); root.addEventListener('focusout', play);
    let x0 = null; root.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; stop(); }, { passive: true });
    root.addEventListener('touchend', (e) => { if (x0 !== null) { const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) show(i + (dx < 0 ? 1 : -1)); } x0 = null; play(); });
    play();
})();
</script>
</body>
</html>
