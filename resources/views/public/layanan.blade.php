@php
    use App\Support\Fmt;
    $login = url('/login');
    $faq = [
        ['Apa saja yang saya dapat?', 'Konten Instagram dan/atau Threads untuk tokomu sesuai paket: ide, desain visual, caption, dan jadwal posting. Rincian tiap paket ada di kartu harga.'],
        ['Bagaimana cara memesan?', 'Pilih paket, masuk dengan Google, lalu konfirmasi pesanan. Kamu akan diarahkan untuk membayar dan konfirmasi lewat WhatsApp. Setelah pembayaran diterima, admin mengaktifkan paketmu.'],
        ['Di mana saya melihat hasilnya?', 'Di halaman akun → Layanan Sosmed. Di sana ada status langganan, jenis paket, masa aktif, dan tautan folder Drive berisi hasil kerja.'],
        ['Apakah saya tetap pegang akun saya?', 'Ya. Akun Instagram dan Threads tetap milikmu. Kami hanya bekerja sesuai akses dan arahan yang kamu berikan.'],
        ['Bisa ganti atau naik paket?', 'Bisa. Hubungi admin lewat WhatsApp dan paketmu akan disesuaikan.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="apple-touch-icon" href="{{ asset('img/brand/temanseller-180.png') }}">
    <title>Jasa Kelola Instagram &amp; Threads — TemanSeller</title>
    <meta name="description" content="Layanan berlangganan kelola konten Instagram dan Threads untuk seller online: ide, desain, caption, dan jadwal posting. Lihat paket dan portofolio.">
    <meta property="og:title" content="Jasa Kelola Instagram &amp; Threads — TemanSeller">
    <meta property="og:description" content="Konten Instagram &amp; Threads tokomu dikelola tim TemanSeller. Pilih paket, lihat portofolio.">
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
        <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
            <a href="{{ $hubUrl }}" class="hover:text-[#ee4d2d]">Beranda</a><a href="#paket" class="hover:text-[#ee4d2d]">Paket</a><a href="#portofolio" class="hover:text-[#ee4d2d]">Portofolio</a><a href="#faq" class="hover:text-[#ee4d2d]">FAQ</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="{{ $login }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:text-[#ee4d2d] sm:block">Masuk</a>
            <a href="#paket" class="rounded-lg bg-[#ee4d2d] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#d7401f]">Lihat paket</a>
        </div>
    </div>
</header>

@if (session('error'))<div class="mx-auto mt-4 max-w-6xl rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700">⚠ {{ session('error') }}</div>@endif

<section class="grad">
    <div class="mx-auto max-w-4xl px-4 pb-16 pt-14 text-center lg:pb-20 lg:pt-20">
        <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white px-3 py-1 text-xs font-semibold text-[#ee4d2d]">📸 Layanan kelola Instagram &amp; Threads</span>
        <h1 class="mt-5 text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">Sosmed tokomu rutin posting, <span class="text-[#ee4d2d]">tanpa kamu repot.</span></h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600">Kami yang menyiapkan ide, desain, caption, dan jadwal posting. Kamu pantau status langganan dan ambil hasilnya dari folder Drive di akunmu.</p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="#paket" class="rounded-xl bg-[#ee4d2d] px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-orange-200 hover:bg-[#d7401f]">Pilih paket →</a>
            @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-base font-semibold text-slate-700 hover:border-[#ee4d2d] hover:text-[#ee4d2d]">Konsultasi via WhatsApp</a>@endif
        </div>
    </div>
</section>

<section class="border-y border-slate-100 bg-white">
    <div class="mx-auto grid max-w-6xl gap-4 px-4 py-6 text-center text-sm font-medium text-slate-600 sm:grid-cols-3">
        <div>🗓️ Konten terjadwal rutin</div><div>🎨 Visual konsisten dengan brand</div><div>📁 Hasil kerja di folder Drive-mu</div>
    </div>
</section>

<section id="paket" class="py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Pilih paket yang pas</h2>
            <p class="mt-3 text-slate-600">Harga jelas, tanpa biaya tersembunyi. Pembayaran dikonfirmasi manual oleh admin.</p>
        </div>
        @forelse ($types as $t)
            <div class="mt-12">
                <h3 class="text-xl font-bold text-slate-900">{{ $t->name }}</h3>
                @if ($t->description)<p class="mt-1 text-sm text-slate-500">{{ $t->description }}</p>@endif
                <div class="mt-5 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($t->plans as $p)
                        <article class="relative flex flex-col rounded-2xl border {{ $p->is_featured ? 'border-[#ee4d2d] shadow-lg shadow-orange-100' : 'border-slate-200 shadow-sm' }} bg-white p-6">
                            @if ($p->badge || $p->is_featured)<span class="absolute -top-3 left-6 rounded-full bg-[#ee4d2d] px-3 py-1 text-xs font-semibold text-white">{{ $p->badge ?: 'Paling diminati' }}</span>@endif
                            <h4 class="text-lg font-bold text-slate-900">{{ $p->name }}</h4>
                            @if ($p->tagline)<p class="mt-1 text-sm text-slate-500">{{ $p->tagline }}</p>@endif
                            <div class="mt-4"><span class="text-3xl font-extrabold text-slate-900">{{ Fmt::rp($p->price) }}</span><span class="text-sm text-slate-500"> {{ $p->period }}</span></div>
                            <ul class="mt-5 flex-1 space-y-2 text-sm text-slate-700">
                                @foreach ($p->featureList() as $f)<li class="flex gap-2"><span class="text-emerald-600">✓</span><span>{{ $f }}</span></li>@endforeach
                            </ul>
                            <a href="{{ route('layanan.start', $p->id) }}" class="mt-6 rounded-xl {{ $p->is_featured ? 'bg-[#ee4d2d] text-white hover:bg-[#d7401f]' : 'border border-slate-200 text-slate-800 hover:border-[#ee4d2d] hover:text-[#ee4d2d]' }} px-4 py-3 text-center text-sm font-semibold">Pesan paket ini</a>
                        </article>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="mx-auto mt-10 max-w-xl rounded-2xl border-2 border-dashed border-orange-200 bg-orange-50/40 p-8 text-center">
                <div class="text-3xl">🚧</div>
                <h3 class="mt-3 text-lg font-bold text-slate-900">Paket sedang disiapkan</h3>
                <p class="mt-2 text-sm text-slate-600">Daftar paket dan harga akan segera tampil di sini. Mau tanya-tanya dulu?</p>
                @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="mt-5 inline-block rounded-xl bg-[#ee4d2d] px-6 py-3 text-sm font-semibold text-white hover:bg-[#d7401f]">Chat via WhatsApp</a>@endif
            </div>
        @endforelse
    </div>
</section>

<section id="cara" class="bg-slate-900 py-20 text-white">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="text-center text-3xl font-extrabold tracking-tight sm:text-4xl">Cara kerjanya</h2>
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['01', 'Pilih paket', 'Tentukan jenis dan paket yang sesuai kebutuhan tokomu.'], ['02', 'Masuk &amp; pesan', 'Masuk dengan Google, konfirmasi pesanan, dan isi catatan brand.'], ['03', 'Bayar &amp; konfirmasi', 'Bayar sesuai petunjuk lalu konfirmasi lewat WhatsApp. Admin mengaktifkan paketmu.'], ['04', 'Ambil hasilnya', 'Pantau status dan buka folder Drive-mu dari halaman akun.']] as [$n, $t, $d])
                <div class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10"><div class="text-sm font-bold text-orange-400">{{ $n }}</div><h3 class="mt-2 font-bold">{!! $t !!}</h3><p class="mt-2 text-sm text-slate-300">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>

<section id="portofolio" class="py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Portofolio</h2>
            <p class="mt-3 text-slate-600">Contoh pekerjaan yang sudah kami kelola.</p>
        </div>
        @if ($portfolio->isEmpty())
            <div class="mx-auto mt-10 max-w-xl rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Portofolio akan segera ditambahkan di sini.</div>
        @else
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($portfolio as $it)
                    @php $link = \App\Services\Layanan::safeUrl($it->link_url); @endphp
                    <article class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        @if ($it->imageUrl())<img src="{{ $it->imageUrl() }}" alt="{{ $it->title }}" loading="lazy" class="aspect-[4/3] w-full object-cover">@else<div class="grid aspect-[4/3] place-items-center bg-orange-50 text-4xl">📸</div>@endif
                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-center justify-between gap-2 text-xs text-slate-500"><span>{{ $it->client_name }}</span>@if ($it->platform)<span class="rounded-full bg-slate-100 px-2.5 py-0.5 font-semibold text-slate-600">{{ $it->platform }}</span>@endif</div>
                            <h3 class="mt-2 font-bold text-slate-900">{{ $it->title }}</h3>
                            @if ($it->description)<p class="mt-1.5 text-sm text-slate-600">{{ $it->description }}</p>@endif
                            @if ($it->result_text)<p class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">📈 {{ $it->result_text }}</p>@endif
                            @if ($link)<a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="mt-4 text-sm font-semibold text-[#ee4d2d] hover:underline">Lihat akun ↗</a>@endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
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
        <h2 class="text-3xl font-extrabold sm:text-4xl">Siap bikin sosmed tokomu hidup?</h2>
        <p class="mx-auto mt-3 max-w-xl text-white/90">Pilih paket atau tanya dulu lewat WhatsApp.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="#paket" class="rounded-xl bg-white px-7 py-3.5 font-semibold text-[#ee4d2d] shadow hover:bg-orange-50">Lihat paket</a>
            @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="rounded-xl border border-white/60 px-7 py-3.5 font-semibold text-white hover:bg-white/10">Chat WhatsApp</a>@endif
        </div>
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
