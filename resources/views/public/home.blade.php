@php
    use App\Services\Plans;
    use App\Support\Fmt;
    $m = $plans['monthly']; $y = $plans['yearly'];
    $mDisc = Plans::discount($m); $yDisc = Plans::discount($y);
    $perMonth = (int) round($y['price'] / max(1, $y['days'] / 30));
    $save = max(0, $m['price'] * (int) round($y['days'] / $m['days']) - $y['price']);
    $cta = url('/login');
    $aiTxt = fn ($n) => $n === null ? 'tanpa batas' : number_format($n, 0, ',', '.') . 'x';
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Riset Produk & Kompetitor Shopee dengan Analisis AI</title>
    <meta name="description" content="Extension Chrome untuk riset produk, toko kompetitor, dan kata kunci Shopee langsung di halaman Shopee. Estimasi omzet, tren penjualan, dan rekomendasi AI. Coba gratis {{ $trial }} hari.">
    <meta property="og:title" content="{{ config('app.name') }} — Riset Shopee + Analisis AI">
    <meta property="og:description" content="Lihat estimasi omzet kompetitor, produk andalan, dan peluang pasar langsung di Shopee. Coba gratis {{ $trial }} hari.">
    @vite(['resources/css/app.css'])
    <style>
        body{font-family:'Outfit',system-ui,sans-serif}
        .grad{background:radial-gradient(1200px 500px at 80% -10%,#ffe3d9 0,transparent 60%),radial-gradient(900px 400px at -10% 10%,#fff1ea 0,transparent 55%),#fffaf7}
        summary::-webkit-details-marker{display:none}
        details[open] .chev{transform:rotate(180deg)}
    </style>
</head>
<body class="bg-white text-slate-800 antialiased">

{{-- NAV --}}
<header class="sticky top-0 z-50 border-b border-orange-100/70 bg-white/85 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-lg font-bold"><span class="grid size-9 place-items-center rounded-xl bg-[#ee4d2d] text-white shadow-sm">🛍️</span>{{ config('app.name') }}</a>
        <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
            <a href="#fitur" class="hover:text-[#ee4d2d]">Fitur</a><a href="#pratinjau" class="hover:text-[#ee4d2d]">Pratinjau</a><a href="#cara" class="hover:text-[#ee4d2d]">Cara Kerja</a><a href="#harga" class="hover:text-[#ee4d2d]">Harga</a><a href="#faq" class="hover:text-[#ee4d2d]">FAQ</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="{{ url('/login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:text-[#ee4d2d] sm:block">Masuk</a>
            <a href="{{ $cta }}" class="rounded-lg bg-[#ee4d2d] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#d7401f]">Coba Gratis {{ $trial }} Hari</a>
        </div>
    </div>
</header>

{{-- HERO --}}
<section class="grad">
    <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 pb-16 pt-14 lg:grid-cols-2 lg:pb-24 lg:pt-20">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white px-3 py-1 text-xs font-semibold text-[#ee4d2d]">✨ Chrome Extension + Analisis AI untuk Seller Shopee</span>
            <h1 class="mt-5 text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">Intip omzet kompetitor &amp; temukan produk laris — <span class="text-[#ee4d2d]">langsung di halaman Shopee.</span></h1>
            <p class="mt-5 max-w-xl text-lg text-slate-600">Buka produk, toko, atau hasil pencarian di Shopee — {{ config('app.name') }} otomatis menampilkan estimasi omzet, tren penjualan, produk andalan, dan rekomendasi aksi dari AI. Tanpa pindah tab, tanpa rekap manual.</p>
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="{{ $cta }}" class="rounded-xl bg-[#ee4d2d] px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-orange-200 hover:bg-[#d7401f]">Coba Gratis {{ $trial }} Hari →</a>
                <a href="#fitur" class="rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-base font-semibold text-slate-700 hover:border-[#ee4d2d] hover:text-[#ee4d2d]">Lihat Fitur</a>
            </div>
            <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-500">
                <li>✓ Daftar pakai Google</li><li>✓ Tanpa kartu kredit</li><li>✓ Mulai {{ Fmt::rp($m['price']) }}/bulan</li>
            </ul>
            @if ($store)<a href="{{ $store }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-700 underline decoration-orange-300 underline-offset-4 hover:text-[#ee4d2d]">Pasang dari Chrome Web Store ↗</a>@endif
        </div>

        {{-- Ilustrasi tampilan --}}
        <div class="relative">
            <div class="absolute -inset-4 -z-10 rounded-[2rem] bg-gradient-to-br from-orange-200/50 to-amber-100/40 blur-2xl"></div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-orange-100">
                <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-4 py-2.5">
                    <span class="size-2.5 rounded-full bg-red-300"></span><span class="size-2.5 rounded-full bg-amber-300"></span><span class="size-2.5 rounded-full bg-green-300"></span>
                    <span class="ml-3 flex-1 truncate rounded-md bg-white px-3 py-1 text-xs text-slate-400">shopee.co.id/toko-contoh</span>
                </div>
                <div class="space-y-3 p-4">
                    <div class="flex items-center gap-3"><div class="size-11 rounded-full bg-orange-100"></div><div><div class="h-3 w-32 rounded bg-slate-200"></div><div class="mt-1.5 h-2.5 w-20 rounded bg-slate-100"></div></div></div>
                    <div class="rounded-xl border border-orange-200 bg-[#fff8f5] p-3.5">
                        <div class="mb-3 flex items-center justify-between"><span class="text-sm font-bold text-[#ee4d2d]">Spill Data</span>
                            <div class="flex gap-1 text-[11px] font-medium"><span class="rounded-md bg-[#ee4d2d] px-2 py-1 text-white">Ringkasan</span><span class="rounded-md bg-white px-2 py-1 text-slate-500">Omset</span><span class="rounded-md bg-white px-2 py-1 text-slate-500">Volume</span><span class="rounded-md bg-violet-100 px-2 py-1 text-violet-700">✨ AI</span></div></div>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-lg bg-white p-2.5"><div class="text-[10px] uppercase text-slate-400">Est. omzet</div><div class="text-base font-bold text-slate-800">Rp128 jt</div></div>
                            <div class="rounded-lg bg-white p-2.5"><div class="text-[10px] uppercase text-slate-400">Terjual</div><div class="text-base font-bold text-slate-800">2,4 rb</div></div>
                            <div class="rounded-lg bg-white p-2.5"><div class="text-[10px] uppercase text-slate-400">Median harga</div><div class="text-base font-bold text-slate-800">Rp54 rb</div></div>
                        </div>
                        <div class="mt-3 flex h-20 items-end gap-1.5 rounded-lg bg-white p-2.5">
                            @foreach ([30, 52, 40, 68, 55, 84, 62, 96, 74, 58] as $h)<div class="flex-1 rounded-t bg-gradient-to-t from-[#ee4d2d] to-orange-300" style="height:{{ $h }}%"></div>@endforeach
                        </div>
                    </div>
                    <div class="rounded-xl border border-violet-200 bg-violet-50/60 p-3.5">
                        <div class="flex items-center gap-3">
                            <div class="grid size-14 shrink-0 place-items-center rounded-full border-4 border-violet-500 bg-white text-lg font-extrabold text-violet-700">82</div>
                            <div class="min-w-0"><div class="text-xs font-semibold text-violet-700">Skor peluang · Potensial</div><div class="mt-0.5 text-[13px] leading-snug text-slate-600">Persaingan sedang, rentang harga Rp40–70 rb paling laku. Fokus foto &amp; judul berkata kunci spesifik.</div></div>
                        </div>
                        <div class="mt-2.5 flex flex-wrap gap-1.5 text-[11px]"><span class="rounded-full bg-white px-2 py-1 text-slate-600">kebaya modern</span><span class="rounded-full bg-white px-2 py-1 text-slate-600">batik couple</span><span class="rounded-full bg-white px-2 py-1 text-slate-600">dress muslimah</span></div>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-center text-xs text-slate-400">Ilustrasi tampilan — angka hanya contoh.</p>
        </div>
    </div>
</section>

{{-- FAKTA CEPAT --}}
<section class="border-y border-slate-100 bg-white">
    <div class="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-4 py-8 text-center md:grid-cols-4">
        @foreach ([['3 halaman', 'Produk, Toko, dan Pencarian'], ['Sampai 20 halaman', 'sekali scan hasil pencarian'], ['CSV & Sheets', 'ekspor data riset sekali klik'], ['Analisis AI', 'skor, risiko, dan rekomendasi']] as [$a, $b])
            <div><div class="text-xl font-extrabold text-slate-900">{{ $a }}</div><div class="mt-0.5 text-sm text-slate-500">{{ $b }}</div></div>
        @endforeach
    </div>
</section>

{{-- PRATINJAU --}}
<section id="pratinjau" class="bg-white py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center">
            <span class="text-sm font-bold uppercase tracking-wider text-[#ee4d2d]">Pratinjau</span>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Begini tampilannya saat dipakai</h2>
            <p class="mt-3 text-slate-600">Klik tab untuk melihat tiap fitur. Ini tampilan asli extension, diisi data contoh.</p>
        </div>
        @php
            $shots = [
                ['toko-ringkasan', 'Ringkasan toko', 'Buka halaman toko kompetitor: estimasi omzet, terjual, dan rentang harga langsung muncul.'],
                ['toko-produk-andalan', 'Produk andalan', 'Lihat produk mana yang menopang omzet toko beserta porsinya.'],
                ['toko-analisa-ai', 'Analisa AI', 'Skor peluang, temuan, risiko, rekomendasi aksi, kata kunci, dan saran judul listing.'],
                ['analisa-detail-grid', 'Analisa Detail (grid)', 'Semua produk toko dalam satu layar: omzet, tren 30 hari, market share, rating, wishlist.'],
                ['analisa-detail-tabel', 'Analisa Detail (tabel)', 'Urutkan, filter, lalu ekspor ke CSV atau salin ke Google Sheets.'],
                ['produk-transaksi', 'Halaman produk', 'Harga, stok, tren 30 hari, nilai jual stok, dan persentase komplain.'],
                ['produk-ulasan', 'Ulasan produk', 'Distribusi bintang dan persen komplain ★1–2 untuk menilai kualitas.'],
                ['pencarian-market', 'Market size', 'Cari kata kunci, lihat total omzet pasar dan seberapa terkonsentrasi di top 10.'],
            ];
        @endphp
        <div class="mt-10 flex flex-wrap justify-center gap-2" id="shot-tabs">
            @foreach ($shots as $i => [$f, $t, $d])
                <button type="button" data-i="{{ $i }}" class="shot-tab rounded-full border px-4 py-2 text-sm font-semibold transition {{ $i === 0 ? 'border-[#ee4d2d] bg-[#ee4d2d] text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-[#ee4d2d] hover:text-[#ee4d2d]' }}">{{ $t }}</button>
            @endforeach
        </div>
        <div class="mx-auto mt-8 max-w-5xl">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-orange-100">
                <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-4 py-2.5"><span class="size-2.5 rounded-full bg-red-300"></span><span class="size-2.5 rounded-full bg-amber-300"></span><span class="size-2.5 rounded-full bg-green-300"></span><span class="ml-3 text-xs text-slate-400">Tampilan extension · data contoh</span></div>
                <a id="shot-link" href="{{ asset('img/preview/' . $shots[0][0] . '.jpg') }}" target="_blank" rel="noopener" class="block bg-slate-100">
                    @foreach ($shots as $i => [$f, $t, $d])
                        <img src="{{ asset('img/preview/' . $f . '.jpg') }}" alt="{{ config('app.name') }}: {{ $t }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" class="shot-img w-full {{ $i === 0 ? '' : 'hidden' }}" data-i="{{ $i }}">
                    @endforeach
                </a>
            </div>
            <p id="shot-cap" class="mt-4 text-center text-base font-medium text-slate-700">{{ $shots[0][2] }}</p>
            <p class="mt-1 text-center text-xs text-slate-400">Angka dan nama toko/produk pada tangkapan layar adalah contoh fiktif. Hasil di Shopee Anda akan berbeda.</p>
        </div>
    </div>
    <script>
        (function () {
            var caps = @json(array_column($shots, 2)), files = @json(array_map(fn ($s) => asset('img/preview/' . $s[0] . '.jpg'), $shots));
            var tabs = document.querySelectorAll('.shot-tab'), imgs = document.querySelectorAll('.shot-img');
            tabs.forEach(function (b) {
                b.addEventListener('click', function () {
                    var i = +b.dataset.i;
                    tabs.forEach(function (x) { var on = x === b; x.className = 'shot-tab rounded-full border px-4 py-2 text-sm font-semibold transition ' + (on ? 'border-[#ee4d2d] bg-[#ee4d2d] text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-[#ee4d2d] hover:text-[#ee4d2d]'); });
                    imgs.forEach(function (m) { m.classList.toggle('hidden', +m.dataset.i !== i); });
                    document.getElementById('shot-cap').textContent = caps[i];
                    document.getElementById('shot-link').href = files[i];
                });
            });
        })();
    </script>
</section>

{{-- MASALAH --}}
<section class="bg-slate-50 py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Riset Shopee manual itu melelahkan</h2>
            <p class="mt-3 text-slate-600">Bolak-balik buka produk, catat harga &amp; terjual di Excel, lalu tetap menebak-nebak. Kenal dengan ini?</p>
        </div>
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['⏳', 'Rekap berjam-jam', 'Menyalin harga dan jumlah terjual satu per satu ke spreadsheet.'], ['🕵️', 'Omzet kompetitor tak terlihat', 'Sulit tahu toko lain sebenarnya sebesar apa dan produk mana yang menopang omzetnya.'], ['🎯', 'Bingung memilih produk &amp; niche', 'Takut masuk pasar yang sudah penuh atau harga tidak masuk akal.'], ['✍️', 'Judul &amp; strategi asal tebak', 'Kata kunci dan harga ditentukan feeling, bukan data.']] as [$i, $t, $d])
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="text-3xl">{{ $i }}</div><h3 class="mt-4 text-lg font-bold text-slate-900">{!! $t !!}</h3><p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>

{{-- FITUR --}}
<section id="fitur" class="py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center">
            <span class="text-sm font-bold uppercase tracking-wider text-[#ee4d2d]">Fitur</span>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Semua yang Anda butuhkan untuk riset, dalam satu extension</h2>
        </div>

        <div class="mt-14 grid gap-6 lg:grid-cols-3">
            <div class="rounded-3xl border border-orange-100 bg-gradient-to-b from-orange-50 to-white p-7">
                <div class="grid size-12 place-items-center rounded-2xl bg-[#ee4d2d] text-2xl text-white">📊</div>
                <h3 class="mt-5 text-xl font-bold text-slate-900">Kartu data langsung di Shopee</h3>
                <p class="mt-2 text-sm text-slate-600">Kartu <b>Spill Data</b> muncul otomatis di halaman yang Anda buka:</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-700">
                    <li><b>Halaman toko</b> — Ringkasan, Omset, Volume, Informasi Umum, Produk Andalan</li>
                    <li><b>Hasil pencarian/kategori</b> — Ringkasan, Market Size, Volume</li>
                    <li><b>Halaman produk</b> — Transaksi, Ulasan, Kompetitor, Varian</li>
                </ul>
            </div>
            <div class="rounded-3xl border border-violet-100 bg-gradient-to-b from-violet-50 to-white p-7">
                <div class="grid size-12 place-items-center rounded-2xl bg-violet-600 text-2xl text-white">✨</div>
                <h3 class="mt-5 text-xl font-bold text-slate-900">Analisa AI siap pakai</h3>
                <p class="mt-2 text-sm text-slate-600">Data hasil scan langsung dianalisis AI, hasilnya tampil di kartu:</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-700">
                    <li><b>Skor peluang</b> &amp; ringkasan temuan berbasis angka</li>
                    <li><b>Peluang &amp; risiko</b> yang perlu diwaspadai</li>
                    <li><b>Rekomendasi aksi</b> berprioritas, plus alasan</li>
                    <li><b>Kata kunci</b> turunan &amp; <b>saran judul</b> listing</li>
                </ul>
            </div>
            <div class="rounded-3xl border border-emerald-100 bg-gradient-to-b from-emerald-50 to-white p-7">
                <div class="grid size-12 place-items-center rounded-2xl bg-emerald-600 text-2xl text-white">🔍</div>
                <h3 class="mt-5 text-xl font-bold text-slate-900">Analisa Detail layar penuh</h3>
                <p class="mt-2 text-sm text-slate-600">Satu klik membuka tabel &amp; grid produk yang bisa diurutkan:</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-700">
                    <li>Filter nama/lokasi, min terjual, rentang harga, min omzet</li>
                    <li>Pindai <b>semua produk toko</b> otomatis, multi-halaman</li>
                    <li>Grafik omzet per rentang harga</li>
                    <li>Ekspor <b>CSV</b> atau salin ke <b>Google Sheets</b></li>
                </ul>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['💰', 'Estimasi omzet', 'Harga × terjual per produk, plus total & median harga.'], ['📈', 'Tren penjualan (Δ)', 'Scan ulang di hari berbeda untuk melihat tambahan terjual.'], ['🏆', 'Produk andalan toko', 'Lihat porsi omzet tiap produk dan peringkat toko.'], ['⭐', 'Ulasan & % komplain', 'Distribusi bintang dan persentase ulasan ★1–2 per produk.'], ['🧩', 'Varian & stok', 'Pilihan varian, stok, dan wishlist untuk produk yang dibuka.'], ['🗂️', 'Peringkat toko', 'Kelompokkan hasil pencarian per toko berdasarkan est. omzet.'], ['🧭', 'Market size', 'Ukuran pasar kata kunci: total omzet, volume, dan sebaran harga.'], ['🛡️', 'Scan aman', 'Jeda acak antar halaman dan berhenti otomatis saat ada captcha.']] as [$i, $t, $d])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-orange-300 hover:shadow-md"><div class="text-2xl">{{ $i }}</div><h4 class="mt-3 font-bold text-slate-900">{{ $t }}</h4><p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>

{{-- CARA KERJA --}}
<section id="cara" class="bg-slate-900 py-20 text-white">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center"><span class="text-sm font-bold uppercase tracking-wider text-orange-400">Cara kerja</span>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Mulai riset dalam 3 langkah</h2></div>
        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ([['1', 'Pasang & masuk dengan Google', 'Pasang extension, login pakai akun Google. Trial ' . $trial . ' hari aktif otomatis.'], ['2', 'Buka halaman Shopee', 'Buka produk, toko kompetitor, atau cari kata kunci. Kartu Spill Data muncul sendiri.'], ['3', 'Scan, baca AI, ambil keputusan', 'Klik Analisa untuk data lengkap, baca rekomendasi AI, lalu ekspor ke CSV atau Sheets.']] as [$n, $t, $d])
                <div class="rounded-2xl border border-white/10 bg-white/5 p-7"><div class="grid size-10 place-items-center rounded-full bg-[#ee4d2d] text-lg font-extrabold">{{ $n }}</div><h3 class="mt-5 text-lg font-bold">{{ $t }}</h3><p class="mt-2 text-sm leading-relaxed text-slate-300">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>

{{-- KEUNGGULAN --}}
<section class="py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center"><span class="text-sm font-bold uppercase tracking-wider text-[#ee4d2d]">Keunggulan</span>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Kenapa seller memilih {{ config('app.name') }}</h2></div>
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([['🔐', 'Tanpa akses akun toko', 'Membaca tampilan halaman Shopee yang Anda buka. Tidak perlu kata sandi Seller Centre atau API Shopee.'], ['🏠', 'Data riset tersimpan lokal', 'Koleksi dan riwayat scan disimpan di browser Anda. Hanya saat memakai Analisa AI, ringkasan data halaman dikirim untuk dianalisis.'], ['⚡', 'Hemat waktu riset', 'Dari berjam-jam rekap manual menjadi beberapa klik, lengkap dengan ekspor siap pakai.'], ['🤖', 'AI yang bicara dengan angka', 'Rekomendasi bersumber dari data halaman yang Anda scan, bukan jawaban generik.'], ['💸', 'Harga bersahabat untuk UMKM', 'Mulai ' . Fmt::rp($m['price']) . ' per bulan. Berhenti kapan saja, tanpa kontrak.'], ['🔄', 'Terus diperbarui', 'Tampilan Shopee berubah? Parser diperbarui agar kolom data tetap terbaca.']] as [$i, $t, $d])
                <div class="flex gap-4 rounded-2xl border border-slate-200 bg-white p-6"><div class="text-3xl">{{ $i }}</div><div><h3 class="font-bold text-slate-900">{{ $t }}</h3><p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $d }}</p></div></div>
            @endforeach
        </div>
    </div>
</section>

{{-- UNTUK SIAPA --}}
<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="text-center text-2xl font-extrabold text-slate-900 sm:text-3xl">Cocok untuk</h2>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['🚀', 'Seller baru', 'Validasi produk &amp; niche sebelum stok barang.'], ['📦', 'Seller berjalan', 'Pantau kompetitor dan atur harga lebih tepat.'], ['🤝', 'Dropshipper &amp; reseller', 'Cari produk laris dengan margin masuk akal.'], ['🧑‍💼', 'Tim &amp; agensi toko', 'Rekap riset cepat, ekspor untuk laporan.']] as [$i, $t, $d])
                <div class="rounded-2xl bg-white p-6 text-center shadow-sm"><div class="text-3xl">{{ $i }}</div><h3 class="mt-3 font-bold text-slate-900">{!! $t !!}</h3><p class="mt-1.5 text-sm text-slate-600">{!! $d !!}</p></div>
            @endforeach
        </div>
    </div>
</section>

{{-- HARGA --}}
<section id="harga" class="py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mx-auto max-w-2xl text-center"><span class="text-sm font-bold uppercase tracking-wider text-[#ee4d2d]">Harga</span>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Coba gratis {{ $trial }} hari, lanjut kalau cocok</h2>
            <p class="mt-3 text-slate-600">Semua paket mendapat semua fitur, termasuk Analisa AI{!! $aiOn ? ' dengan batas pemakaian harian sesuai paket' : '' !!}.</p></div>

        <div class="mx-auto mt-12 grid max-w-5xl gap-6 lg:grid-cols-3">
            {{-- Trial --}}
            <div class="flex flex-col rounded-3xl border border-slate-200 bg-white p-7">
                <div class="text-sm font-semibold text-slate-500">Trial</div>
                <div class="mt-3 flex items-baseline gap-1"><span class="text-4xl font-extrabold text-slate-900">Gratis</span></div>
                <div class="text-sm text-slate-500">selama {{ $trial }} hari</div>
                <ul class="mt-6 flex-1 space-y-2.5 text-sm text-slate-700"><li>✓ Semua fitur riset</li><li>✓ Analisa AI{!! $aiOn ? ' · ' . e($aiTxt($aiq['trial'])) . '/hari' : '' !!}</li><li>✓ Ekspor CSV &amp; Sheets</li><li>✓ Tanpa kartu kredit</li></ul>
                <a href="{{ $cta }}" class="mt-7 block rounded-xl border-2 border-[#ee4d2d] px-5 py-3 text-center font-semibold text-[#ee4d2d] hover:bg-orange-50">Mulai Trial Gratis</a>
            </div>
            {{-- Bulanan --}}
            <div class="flex flex-col rounded-3xl border border-slate-200 bg-white p-7">
                <div class="flex items-center justify-between"><span class="text-sm font-semibold text-slate-500">{{ $m['label'] }}</span>@if ($mDisc)<span class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-bold text-[#ee4d2d]">Diskon {{ $mDisc }}%</span>@endif</div>
                <div class="mt-3">@if ($m['original'] > $m['price'])<s class="text-lg text-slate-400">{{ Fmt::rp($m['original']) }}</s>@endif</div>
                <div class="flex items-baseline gap-1"><span class="text-4xl font-extrabold text-slate-900">{{ Fmt::rp($m['price']) }}</span><span class="text-slate-500">/ bulan</span></div>
                <div class="text-sm text-slate-500">Ditagih per {{ $m['days'] }} hari</div>
                <ul class="mt-6 flex-1 space-y-2.5 text-sm text-slate-700"><li>✓ Semua fitur &amp; Analisa AI{!! $aiOn ? ' · <b>' . e($aiTxt($aiq['monthly'])) . '/hari</b>' : '' !!}</li><li>✓ Perpanjang kapan saja</li><li>✓ Berhenti kapan saja</li><li>✓ Sisa hari tidak hangus saat perpanjang</li></ul>
                <a href="{{ route('portal.buy', 'monthly') }}" class="mt-7 block rounded-xl bg-slate-900 px-5 py-3 text-center font-semibold text-white hover:bg-slate-800">Pilih Bulanan</a>
            </div>
            {{-- Tahunan --}}
            <div class="relative flex flex-col rounded-3xl border-2 border-[#ee4d2d] bg-gradient-to-b from-orange-50 to-white p-7 shadow-xl shadow-orange-100">
                <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-[#ee4d2d] px-4 py-1 text-xs font-bold uppercase tracking-wide text-white">Paling hemat</span>
                <div class="flex items-center justify-between"><span class="text-sm font-semibold text-[#ee4d2d]">{{ $y['label'] }}</span>@if ($yDisc)<span class="rounded-full bg-[#ee4d2d] px-2.5 py-0.5 text-xs font-bold text-white">Diskon {{ $yDisc }}%</span>@endif</div>
                <div class="mt-3">@if ($y['original'] > $y['price'])<s class="text-lg text-slate-400">{{ Fmt::rp($y['original']) }}</s>@endif</div>
                <div class="flex items-baseline gap-1"><span class="text-4xl font-extrabold text-slate-900">{{ Fmt::rp($y['price']) }}</span><span class="text-slate-500">/ tahun</span></div>
                <div class="text-sm font-medium text-emerald-700">≈ {{ Fmt::rp($perMonth) }} per bulan {!! $save > 0 ? '· hemat ' . e(Fmt::rp($save)) . ' dari Bulanan' : '' !!}</div>
                <ul class="mt-6 flex-1 space-y-2.5 text-sm text-slate-700"><li>✓ Semua fitur &amp; Analisa AI{!! $aiOn ? ' · <b>' . e($aiTxt($aiq['yearly'])) . '/hari</b>' : '' !!}</li><li>✓ Bayar sekali untuk {{ $y['days'] }} hari</li><li>✓ Harga terkunci selama setahun</li><li>✓ Pembaruan fitur gratis</li></ul>
                <a href="{{ route('portal.buy', 'yearly') }}" class="mt-7 block rounded-xl bg-[#ee4d2d] px-5 py-3 text-center font-semibold text-white shadow-lg shadow-orange-200 hover:bg-[#d7401f]">Pilih Tahunan</a>
            </div>
        </div>
        <p class="mx-auto mt-8 max-w-2xl text-center text-sm text-slate-500">Klik paket → masuk dengan Google → langsung ke halaman pembayaran aman <b>Midtrans</b>. Akses aktif otomatis setelah pembayaran berhasil. Anda bisa melihat status &amp; memperpanjang kapan saja di <a href="{{ url('/login') }}" class="font-semibold text-[#ee4d2d] underline">halaman akun</a>.</p>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="bg-slate-50 py-20">
    <div class="mx-auto max-w-3xl px-4">
        <h2 class="text-center text-3xl font-extrabold tracking-tight text-slate-900">Pertanyaan yang sering ditanyakan</h2>
        <div class="mt-10 space-y-3">
            @foreach ([
                ['Apakah angka omzetnya akurat?', 'Omzet adalah estimasi (harga × jumlah terjual). Shopee membulatkan angka terjual di tampilannya (mis. "10RB+"), jadi hasilnya berupa estimasi batas bawah — sangat berguna untuk membandingkan, bukan angka pasti.'],
                ['Apakah butuh akses akun Shopee atau Seller Centre saya?', 'Tidak. Extension membaca tampilan halaman Shopee yang sedang Anda buka, tanpa meminta kata sandi dan tanpa API Shopee.'],
                ['Data apa yang dikirim ke server?', 'Koleksi dan riwayat riset tersimpan lokal di browser Anda. Saat Anda memakai Analisa AI, ringkasan data halaman yang di-scan dikirim ke server kami untuk dianalisis, lalu hasilnya dikembalikan ke kartu.'],
                ['Apa yang terjadi setelah trial ' . $trial . ' hari habis?', 'Akses berhenti sampai Anda memilih paket. Tidak ada penagihan otomatis dan tidak perlu kartu kredit untuk mencoba.'],
                ['Bagaimana cara memperpanjang atau melihat status langganan?', 'Masuk ke halaman akun dengan Google. Di sana tampak status, tanggal berakhir, sisa hari, riwayat pembayaran, dan tombol perpanjang. Sisa hari tidak hangus saat Anda memperpanjang.'],
                ['Metode pembayaran apa yang tersedia?', 'Pembayaran diproses lewat Midtrans. Metode yang tampil di halaman pembayaran mengikuti yang tersedia di Midtrans saat Anda checkout.'],
                ['Apakah akun Shopee saya bisa dibatasi karena scraping?', 'Scan memakai jeda acak antar halaman dan berhenti otomatis bila Shopee meminta verifikasi/captcha. Tetap gunakan secukupnya; Shopee dapat membatasi akses yang berlebihan.'],
                ['Browser apa yang didukung?', 'Google Chrome dan browser berbasis Chromium di komputer (Windows, macOS, Linux). Extension tidak berjalan di aplikasi Shopee mobile.'],
            ] as [$q, $a])
                <details class="group rounded-2xl border border-slate-200 bg-white px-5 py-4 open:shadow-sm">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold text-slate-900">{{ $q }}<svg class="chev size-5 shrink-0 text-slate-400 transition" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 8l5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg></summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA AKHIR --}}
<section class="px-4 py-16">
    <div class="mx-auto max-w-5xl overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#ee4d2d] to-orange-500 p-10 text-center text-white shadow-xl sm:p-14">
        <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Siap riset lebih cepat &amp; lebih cerdas?</h2>
        <p class="mx-auto mt-3 max-w-xl text-orange-50">Daftar dengan Google, coba semua fitur gratis {{ $trial }} hari. Tanpa kartu kredit.</p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="{{ $cta }}" class="rounded-xl bg-white px-7 py-3.5 font-semibold text-[#ee4d2d] shadow hover:bg-orange-50">Coba Gratis {{ $trial }} Hari</a>
            <a href="#harga" class="rounded-xl border border-white/60 px-7 py-3.5 font-semibold text-white hover:bg-white/10">Lihat Harga</a>
        </div>
    </div>
</section>

<footer class="border-t border-slate-100 bg-white">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-slate-500 sm:flex-row">
        <div class="flex items-center gap-2 font-semibold text-slate-700"><span class="grid size-7 place-items-center rounded-lg bg-[#ee4d2d] text-sm text-white">🛍️</span>{{ config('app.name') }}</div>
        <div class="flex gap-5"><a href="#fitur" class="hover:text-[#ee4d2d]">Fitur</a><a href="#harga" class="hover:text-[#ee4d2d]">Harga</a><a href="#faq" class="hover:text-[#ee4d2d]">FAQ</a><a href="{{ url('/login') }}" class="hover:text-[#ee4d2d]">Akun</a></div>
        <div>© {{ date('Y') }} {{ config('app.name') }}. Bukan produk resmi Shopee. <span class="text-xs text-slate-400">v{{ config('spill.version') }}</span></div>
    </div>
</footer>
</body>
</html>
