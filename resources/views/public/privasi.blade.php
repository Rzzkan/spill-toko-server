<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="apple-touch-icon" href="{{ asset('img/brand/temanseller-180.png') }}">
    <title>Kebijakan Privasi · TemanSeller &amp; Shofee</title>
    <meta name="description" content="Kebijakan privasi TemanSeller dan extension Chrome Shofee: data apa yang dikumpulkan, untuk apa, dan hak Anda.">
    @vite(['resources/css/app.css'])
<style>
.policy{line-height:1.7}.policy>*+*{margin-top:1rem}
.policy h1{font-size:1.9rem;font-weight:800;color:#111827}
.policy h2{margin-top:2.25rem;font-size:1.25rem;font-weight:700;color:#111827}
.policy ul{list-style:disc;padding-left:1.5rem}.policy li+li{margin-top:.4rem}
.policy a{color:#ee4d2d;font-weight:500;text-decoration:underline}
.policy code{background:#f3f4f6;padding:1px 5px;border-radius:4px;font-size:.9em}
</style>
</head>
<body class="bg-gray-50 text-gray-800">
<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
        <x-brand :href="url('/')" size="size-12" class="text-lg font-semibold" />
        <a href="{{ url('/shofee') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Shofee</a>
    </div>
</header>
<main class="mx-auto max-w-3xl px-4 py-10">
<article class="policy">
    <h1 class="text-3xl font-extrabold text-gray-900">Kebijakan Privasi</h1>
    <p class="text-sm text-gray-500">Berlaku sejak 3 November 2026. Berlaku untuk situs <b>temanseller.com</b>, layanan akun TemanSeller, dan extension Chrome <b>Shofee</b>.</p>
    <p>TemanSeller menyediakan alat bantu untuk seller online. Halaman ini menjelaskan data apa yang kami kumpulkan, untuk apa kami memakainya, dan pilihan yang Anda miliki. Kami tidak menjual data Anda.</p>

    <h2>1. Data yang kami kumpulkan</h2>
    <ul>
        <li><b>Informasi akun.</b> Saat Anda masuk dengan Google: ID akun Google, nama, alamat email, dan foto profil. Kami tidak menerima atau menyimpan kata sandi Google Anda.</li>
        <li><b>Informasi perangkat &amp; browser terhubung.</b> Nama browser dan sistem operasi, alamat IP, serta waktu terakhir aktif, untuk membatasi jumlah browser per akun dan menjaga keamanan.</li>
        <li><b>Status langganan &amp; pembayaran.</b> Paket, masa aktif trial atau langganan, nomor pesanan, nominal, dan status pembayaran. Pembayaran diproses oleh Midtrans; kami tidak menyimpan nomor kartu atau data rekening Anda.</li>
        <li><b>Data halaman marketplace yang Anda pindai (konten situs web).</b> Saat Anda memakai Shofee di halaman shopee.co.id, extension membaca data produk dan toko yang tampil di halaman itu, seperti nama, harga, stok, jumlah terjual, rating, dan lokasi toko. Data ini disimpan di perangkat Anda. Ringkasan angka produk (identitas produk, harga, jumlah terjual) dikirim ke server kami untuk menghitung tren 30 hari.</li>
        <li><b>Permintaan Analisa AI.</b> Ringkasan data produk atau toko yang Anda minta dianalisis dikirim ke server kami lalu diteruskan ke penyedia AI (kenari.id) untuk membuat analisa. Kami menyimpan hasilnya sementara (cache) dan jumlah pemakaian untuk menerapkan kuota harian.</li>
        <li><b>Log teknis.</b> Catatan kesalahan dan aktivitas server yang diperlukan untuk menjaga layanan tetap berjalan dan aman.</li>
    </ul>

    <h2>2. Untuk apa data dipakai</h2>
    <ul>
        <li>Menjalankan fitur Shofee: menampilkan data, tren, dan Analisa AI.</li>
        <li>Mengelola akun, trial, langganan, dan pembayaran Anda.</li>
        <li>Membatasi penyalahgunaan dan menjaga keamanan layanan.</li>
        <li>Menghubungi Anda terkait akun atau pembayaran bila diperlukan.</li>
    </ul>
    <p>Kami tidak memakai data Anda untuk iklan, tidak menjualnya kepada pihak lain, dan tidak memakainya untuk menilai kelayakan kredit.</p>

    <h2>3. Penyimpanan di extension</h2>
    <p>Shofee menyimpan koleksi produk yang Anda pindai, preferensi, dan progres pemindaian di penyimpanan lokal browser (<code>chrome.storage.local</code>). Data ini tetap di perangkat Anda dan dapat dihapus kapan saja lewat tombol hapus di panel Shofee atau dengan mencopot extension. Izin <i>clipboardWrite</i> hanya dipakai saat Anda menekan tombol salin ke Google Sheets.</p>

    <h2>4. Pihak ketiga</h2>
    <ul>
        <li><b>Google</b>, untuk proses masuk (Sign in with Google).</li>
        <li><b>Midtrans</b>, untuk pemrosesan pembayaran.</li>
        <li><b>kenari.id</b>, penyedia model AI untuk fitur Analisa AI.</li>
    </ul>
    <p>Kami hanya membagikan data yang diperlukan agar layanan tersebut berjalan. Kami juga dapat mengungkapkan data bila diwajibkan oleh hukum yang berlaku.</p>

    <h2>5. Penyimpanan &amp; keamanan</h2>
    <p>Data akun disimpan di server kami dan dikirim melalui koneksi terenkripsi (HTTPS). Kami menyimpan data selama akun Anda aktif atau selama diperlukan untuk menyediakan layanan dan memenuhi kewajiban hukum, lalu menghapusnya bila sudah tidak diperlukan.</p>

    <h2>6. Hak Anda</h2>
    <p>Anda dapat meminta akses, koreksi, atau penghapusan akun dan data Anda, serta melepas browser yang terhubung kapan saja di <a href="{{ url('/akun') }}">halaman akun</a>. Untuk permintaan penghapusan akun{!! $email ? ', hubungi kami di <a href="mailto:' . e($email) . '">' . e($email) . '</a>' : '' !!}.</p>

    <h2>7. Anak-anak</h2>
    <p>Layanan ini ditujukan untuk pelaku usaha dan tidak ditujukan bagi anak di bawah 18 tahun.</p>

    <h2>8. Perubahan kebijakan</h2>
    <p>Bila kebijakan ini berubah, versi terbaru akan ditampilkan di halaman ini beserta tanggal berlakunya.</p>

    <h2>9. Kontak</h2>
    <p>{!! $email ? 'Pertanyaan tentang privasi: <a href="mailto:' . e($email) . '">' . e($email) . '</a>.' : 'Pertanyaan tentang privasi dapat disampaikan melalui halaman akun Anda.' !!}</p>
    <p class="text-xs text-gray-400">Shofee dan TemanSeller tidak berafiliasi dengan marketplace mana pun. Data yang ditampilkan bersifat estimasi dan bukan data resmi marketplace.</p>
</article>
</main>
<footer class="border-t border-gray-200 bg-white py-6 text-center text-xs text-gray-400">© {{ date('Y') }} TemanSeller</footer>
</body>
</html>
