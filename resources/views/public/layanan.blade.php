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
    <div class="mx-auto max-w-4xl px-4 pb-8 pt-10 text-center lg:pb-10 lg:pt-14">
        <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white px-3 py-1 text-xs font-semibold text-[#ee4d2d]">📸 Layanan kelola Instagram &amp; Threads</span>
        <h1 class="mt-4 text-3xl font-extrabold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">Sosmed tokomu rutin posting, <span class="text-[#ee4d2d]">tanpa kamu repot.</span></h1>
        <p class="mx-auto mt-4 max-w-2xl text-base text-slate-600 sm:text-lg">Kami yang menyiapkan ide, desain, caption, dan jadwal posting. Kamu pantau status langganan dan ambil hasilnya dari folder Drive di akunmu.</p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3 text-sm font-medium text-slate-600"><span>🗓️ Konten terjadwal rutin</span><span>🎨 Visual konsisten dengan brand</span><span>📁 Hasil di folder Drive-mu</span></div>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="#paket" class="rounded-xl bg-[#ee4d2d] px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-orange-200 hover:bg-[#d7401f]">Pilih paket →</a>
            @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-base font-semibold text-slate-700 hover:border-[#ee4d2d] hover:text-[#ee4d2d]">Konsultasi via WhatsApp</a>@endif
        </div>
    </div>
</section>

@if (!empty($soon))
<section class="pb-2 pt-2"><div class="mx-auto max-w-3xl px-4">@foreach ($soon as $x)
    <div class="mb-3 flex items-center justify-between gap-3 rounded-2xl border border-dashed border-violet-300 bg-violet-50/60 px-5 py-4">
        <div><div class="font-bold text-slate-800">🚀 {{ $x }}</div><p class="mt-0.5 text-sm text-slate-500">Layanan ini sedang kami siapkan dan akan tersedia segera.</p></div>
        <span class="shrink-0 whitespace-nowrap rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">Segera hadir</span>
    </div>@endforeach</div></section>
@endif
<section id="paket" class="pb-12 pt-4">
    <div class="mx-auto max-w-6xl px-4">
        @if ($types->isEmpty())
            <div class="mx-auto max-w-xl rounded-2xl border-2 border-dashed border-orange-200 bg-orange-50/40 p-8 text-center">
                <div class="text-3xl">🚧</div>
                <h3 class="mt-3 text-lg font-bold text-slate-900">Paket sedang disiapkan</h3>
                <p class="mt-2 text-sm text-slate-600">Daftar paket dan harga akan segera tampil di sini. Mau tanya-tanya dulu?</p>
                @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="mt-5 inline-block rounded-xl bg-[#ee4d2d] px-6 py-3 text-sm font-semibold text-white hover:bg-[#d7401f]">Chat via WhatsApp</a>@endif
            </div>
        @else
            <div class="-mx-4 overflow-x-auto px-4 pb-2">
                <div id="tabs" role="tablist" class="mx-auto flex w-max max-w-none gap-1.5 rounded-full border border-slate-200 bg-white p-1.5 shadow-sm">
                    @foreach ($types as $i => $t)
                        <button type="button" role="tab" data-tab="{{ $t->slug }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" class="tab whitespace-nowrap rounded-full px-5 py-2.5 text-sm font-semibold transition {{ $i === 0 ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ $t->name }}</button>
                    @endforeach
                </div>
            </div>
            <p class="mt-3 text-center text-sm text-slate-500">Harga jelas, tanpa biaya tersembunyi. Pembayaran dikonfirmasi manual oleh admin.</p>
            @foreach ($types as $i => $t)
                <div data-panel="{{ $t->slug }}" role="tabpanel" class="mt-8 {{ $i === 0 ? '' : 'hidden' }}">
                    @if ($t->description)<p class="mb-6 text-center text-sm text-slate-500">{{ $t->description }}</p>@endif
                    <div class="flex flex-wrap items-stretch justify-center gap-6">
                        @foreach ($t->plans as $p)
                            @php $f = $p->is_featured; @endphp
                            <article class="relative flex w-full flex-col rounded-3xl p-7 md:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)] {{ $f ? 'bg-[#1a1033] text-white shadow-2xl shadow-violet-200 lg:-my-3 lg:py-9' : 'border border-slate-200 bg-white shadow-sm' }}">
                                @if ($p->badge || $f)<span class="absolute right-6 top-6 rounded-full bg-gradient-to-r from-violet-500 to-orange-400 px-3.5 py-1.5 text-xs font-bold text-white">{{ $p->badge ?: 'Rekomendasi' }}</span>@endif
                                <h4 class="text-base font-bold {{ $f ? 'text-white' : 'text-violet-900' }}">{{ $p->name }}</h4>
                                @if ($p->discountPct() > 0)<div class="mt-3 flex items-center gap-2 text-sm"><span class="line-through {{ $f ? 'text-slate-400' : 'text-slate-400' }}">{{ Fmt::rpShort($p->original_price) }}</span><span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Hemat {{ $p->discountPct() }}%</span></div>@endif
                                <div class="{{ $p->discountPct() > 0 ? 'mt-1' : 'mt-3' }} flex items-end gap-2"><span class="text-4xl font-extrabold tracking-tight">{{ Fmt::rpShort($p->price) }}</span><span class="pb-1.5 text-sm font-semibold {{ $f ? 'text-violet-300' : 'text-slate-500' }}">{{ $p->period }}</span></div>
                                @if ($p->tagline)<p class="mt-1 text-sm {{ $f ? 'text-slate-300' : 'text-slate-500' }}">{{ $p->tagline }}</p>@endif
                                <div class="my-5 border-t border-dashed {{ $f ? 'border-white/20' : 'border-slate-200' }}"></div>
                                <ul class="flex-1 space-y-3 text-sm {{ $f ? 'text-slate-100' : 'text-slate-700' }}">
                                    @foreach ($p->featureList() as $ft)<li class="flex gap-3"><span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full text-[11px] font-bold {{ $f ? 'bg-white/15 text-violet-200' : 'bg-violet-100 text-violet-600' }}">✓</span><span>{{ $ft }}</span></li>@endforeach
                                </ul>
                                @if ($p->addon)<p class="mt-4 rounded-lg px-3 py-2 text-xs {{ $f ? 'bg-white/10 text-slate-200' : 'bg-violet-50 text-violet-800' }}"><b>Add-on:</b> {{ $p->addon }}</p>@endif
                                <a href="{{ route('layanan.start', $p->id) }}" class="mt-7 rounded-xl px-4 py-3 text-center text-sm font-semibold {{ $f ? 'bg-white text-[#1a1033] hover:bg-orange-50' : 'bg-[#1a1033] text-white hover:bg-slate-800' }}">Pilih paket ini</a>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</section>

<section id="cara" class="bg-slate-900 py-12 text-white">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="text-center text-2xl font-extrabold tracking-tight sm:text-3xl">Cara kerjanya</h2>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['01', 'Pilih paket', 'Tentukan jenis dan paket yang sesuai kebutuhan tokomu.'], ['02', 'Masuk &amp; pesan', 'Masuk dengan Google, konfirmasi pesanan, dan isi catatan brand.'], ['03', 'Bayar &amp; konfirmasi', 'Bayar sesuai petunjuk lalu konfirmasi lewat WhatsApp. Admin mengaktifkan paketmu.'], ['04', 'Ambil hasilnya', 'Pantau status dan buka folder Drive-mu dari halaman akun.']] as [$n, $t, $d])
                <div class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10"><div class="text-sm font-bold text-orange-400">{{ $n }}</div><h3 class="mt-2 font-bold">{!! $t !!}</h3><p class="mt-2 text-sm text-slate-300">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>

<section id="portofolio" class="py-12">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Portofolio</h2>
            <p class="mt-3 text-slate-600">Contoh pekerjaan yang sudah kami kelola.</p>
        </div>
        @if ($portfolio->isEmpty())
            <div class="mx-auto mt-10 max-w-xl rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Portofolio akan segera ditambahkan di sini.</div>
        @else
            <div class="-mx-4 mt-8 flex snap-x gap-5 overflow-x-auto px-4 pb-4">
                @foreach ($portfolio as $it)
                    @php $link = \App\Services\Layanan::safeUrl($it->link_url); @endphp
                    <article class="flex w-72 shrink-0 snap-start flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:w-80">
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

<section id="faq" class="bg-slate-50 py-12">
    <div class="mx-auto max-w-5xl px-4">
        <h2 class="text-center text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Pertanyaan umum</h2>
        <div class="mt-8 grid items-start gap-3 md:grid-cols-2">
            @foreach ($faq as [$q, $a])
                <details class="group rounded-xl bg-white p-5 ring-1 ring-slate-100"><summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold text-slate-900">{{ $q }}<span class="chev text-slate-400 transition">⌄</span></summary><p class="mt-3 text-sm text-slate-600">{{ $a }}</p></details>
            @endforeach
        </div>
    </div>
</section>

<section class="px-4 py-10">
    <div class="mx-auto max-w-5xl rounded-3xl bg-gradient-to-br from-[#ee4d2d] to-[#ff7a45] px-6 py-10 text-center text-white">
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
<script>
(() => {
    const tabs = [...document.querySelectorAll('.tab')], panels = [...document.querySelectorAll('[data-panel]')];
    if (!tabs.length) return;
    const on = ['bg-slate-900', 'text-white'], off = ['text-slate-600', 'hover:bg-slate-100'];
    function pick(slug, push) {
        if (!tabs.some((t) => t.dataset.tab === slug)) slug = tabs[0].dataset.tab;
        tabs.forEach((t) => { const a = t.dataset.tab === slug; t.setAttribute('aria-selected', a); on.forEach((c) => t.classList.toggle(c, a)); off.forEach((c) => t.classList.toggle(c, !a)); });
        panels.forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== slug));
        if (push) { try { history.replaceState(null, '', '?tab=' + encodeURIComponent(slug) + '#paket'); } catch (_) {} }
    }
    tabs.forEach((t) => t.addEventListener('click', () => pick(t.dataset.tab, true)));
    pick(new URLSearchParams(location.search).get('tab') || tabs[0].dataset.tab, false);
})();
</script>
</body>
</html>
