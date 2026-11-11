@extends('layouts.portal', ['title' => 'Panduan Swara'])
@section('nav')
<a href="{{ route('swara.page') }}" class="mr-4 text-sm text-gray-500 hover:text-gray-800">← Kembali ke Swara</a>
<form method="POST" action="{{ route('portal.logout') }}" class="inline">@csrf<button class="text-sm text-gray-500 hover:text-gray-800">Keluar</button></form>
@endsection
@section('content')
@php
    $videoCard = function (string $title, string $desc, ?string $url) {
        return compact('title', 'desc', 'url');
    };
@endphp

<div class="overflow-hidden rounded-3xl border border-orange-100 bg-ts-navy text-white">
    <div class="grid gap-6 p-8 sm:grid-cols-[1fr_auto] sm:items-center">
        <div>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-orange-200">Panduan Penggunaan</span>
            <h1 class="mt-3 text-2xl font-extrabold sm:text-3xl">Swara — Cloning Suara Juragan Sendiri</h1>
            <p class="mt-2 max-w-xl text-sm text-slate-200">Panduan ini disusun biar kamu bisa langsung jalan, Juragan — dari cara masuk, nyambungin notebook, sampai menghasilkan audio pertamamu.</p>
        </div>
        <img src="{{ asset('img/slides/swara.webp') }}" alt="Maskot TemanSeller mengacungkan jempol di samping mikrofon" class="hidden h-36 w-auto rounded-2xl sm:block">
    </div>
</div>

<section class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
    <h2 class="text-lg font-bold text-ts-navy">Pendahuluan</h2>
    <p class="mt-3 text-sm leading-relaxed text-gray-600">Selamat datang di <b>Swara</b>, Juragan. Ini alat buat mengubah naskah jadi audio pakai suaramu sendiri — tanpa rekam ulang berkali-kali, tanpa biaya langganan bulanan.</p>
    <p class="mt-3 text-sm leading-relaxed text-gray-600">Mesinnya (OmniVoice) jalan di Google Colab-mu sendiri, gratis dari Google, bukan di server TemanSeller. Jadi suaramu tidak pernah dikirim ke kami — ini yang bikin Swara bisa sekali bayar, bukan langganan. Konsekuensinya satu: notebook itu perlu kamu jalankan tiap mau pakai, dan satu lisensi cuma buat satu sesi aktif.</p>
    <p class="mt-3 text-sm leading-relaxed text-gray-600">Panduan ini pelan-pelan saja, satu langkah demi satu langkah. Kalau ada yang kurang jelas, boleh tanya lewat WhatsApp — tautannya ada di halaman Layanan.</p>
</section>

<section class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
    <h2 class="text-lg font-bold text-ts-navy">Daftar isi</h2>
    <ol class="mt-3 space-y-1.5 text-sm text-ts-orange-text">
        <li><a href="#awal" class="hover:underline">1. Panduan Awal</a> — cara akses Swara, cara menyambungkan notebook / membuka sesi baru</li>
        <li><a href="#pakai" class="hover:underline">2. Penggunaan Tool</a> — Voice Cloning (meniru suara)</li>
        <li><a href="#penutup" class="hover:underline">3. Penutup</a></li>
    </ol>
</section>

<section id="awal" class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
    <h2 class="text-lg font-bold text-ts-navy">1. Panduan Awal</h2>
    <p class="mt-2 text-sm text-gray-500">Bagian ini soal cara masuk ke Swara dari nol, Juragan.</p>

    <div class="mt-5 border-t border-gray-100 pt-5">
        <h3 class="font-bold text-ts-navy">Cara akses &amp; beli lisensi</h3>
        <p class="mt-1.5 text-sm text-gray-600">Lisensi Swara dibeli sekali di <a href="{{ route('swara.landing') }}" class="text-ts-orange-text hover:underline">temanseller.com/swara</a>, masuk pakai akun Google yang sama dengan akun TemanSeller-mu. Setelah pembayaran berhasil, lisensinya aktif otomatis dan kamu bisa langsung buka halaman Swara.</p>
        @include('portal._swara-video', $videoCard('Cara akses & beli lisensi Swara', 'Langkah masuk dan membeli lisensi, dari awal.', $videos['akses']))
    </div>

    <div class="mt-6 border-t border-gray-100 pt-5">
        <h3 class="font-bold text-ts-navy">Cara menyambungkan notebook / membuka sesi baru</h3>
        <p class="mt-1.5 text-sm text-gray-600">Setiap mau pakai Swara, kamu jalankan notebook Colab-nya dulu. Begini urutannya:</p>
        <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-gray-600">
            <li>Buka notebook <i>Swara_Worker.ipynb</i> di Google Colab, pilih <b>Runtime → Change runtime type → T4 GPU</b>.</li>
            <li>Jalankan semua sel (<b>Runtime → Run all</b>), tunggu sampai sel terakhir mencetak sebuah link.</li>
            <li>Buka link itu, atau salin lalu tempel di halaman <a href="{{ route('swara.page') }}" class="text-ts-orange-text hover:underline">Swara</a> milikmu.</li>
            <li>Biarkan tab notebook tetap terbuka selama kamu pakai Swara. Menjalankan notebook baru otomatis membuka sesi baru dan mengambil alih dari sesi lama — jadi kalau tiba-tiba halaman Swara bilang "sesi digantikan", itu tandanya ada notebook lain yang baru dijalankan dengan akun yang sama.</li>
        </ol>
        <p class="mt-2 text-xs text-gray-500">Google Colab gratis bisa memutus sesi kapan saja, Juragan. Wajar, bukan tanda ada yang salah — tinggal jalankan ulang notebooknya dan tempel link barunya.</p>
        @include('portal._swara-video', $videoCard('Cara menyambungkan notebook', 'Jalankan notebook, salin link, tempel di halaman Swara.', $videos['sesi']))
    </div>
</section>

<section id="pakai" class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
    <h2 class="text-lg font-bold text-ts-navy">2. Penggunaan Tool</h2>
    <p class="mt-2 text-sm text-gray-500">Satu fitur utama Swara saat ini:</p>

    <div class="mt-5">
        <h3 class="font-bold text-ts-navy">Voice Cloning (meniru suara)</h3>
        <p class="mt-1.5 text-sm text-gray-600">Fitur ini meniru karakter suara dari contoh audio yang kamu unggah, lalu mengucapkan naskah apa pun dengan suara itu. Langkahnya di halaman Swara:</p>
        <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-gray-600">
            <li>Unggah contoh suara — rekaman jernih 10–30 detik, suaramu sendiri atau suara yang kamu punya izin memakainya.</li>
            <li>Tulis naskah yang mau diucapkan.</li>
            <li>Centang kotak persetujuan (kamu punya suara itu atau izin pemiliknya, dan tidak dipakai buat menipu atau menyamar sebagai orang lain).</li>
            <li>Klik <b>Hasilkan suara</b>, tunggu beberapa puluh detik, lalu dengarkan dan unduh hasilnya langsung dari browser.</li>
        </ol>
        @include('portal._swara-video', $videoCard('Cara memakai Voice Cloning', 'Unggah referensi, tulis naskah, hasilkan audio.', $videos['clone']))
    </div>
</section>

<section id="penutup" class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
    <h2 class="text-lg font-bold text-ts-navy">3. Penutup</h2>
    <p class="mt-2 text-sm leading-relaxed text-gray-600">Sampai sini, Juragan sudah tahu cara jalanin Swara dari awal sampai audio pertama selesai. Pelan-pelan saja — notebook yang kadang putus sesi itu wajar, tinggal dijalankan ulang.</p>
    <p class="mt-2 text-sm leading-relaxed text-gray-600">Dipakai buat narasi konten, voice over, atau apa pun yang butuh suaramu sendiri. Kalau ada yang masih bingung, boleh cerita atau tanya lewat WhatsApp — ada di halaman Layanan.</p>
</section>
@endsection
