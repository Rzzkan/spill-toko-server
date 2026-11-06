<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="apple-touch-icon" href="{{ asset('img/brand/temanseller-180.png') }}">
    <title>Kebijakan Privasi · TemanSeller &amp; Shofee</title>
    <meta name="description" content="Kebijakan privasi TemanSeller dan extension Chrome Shofee: data apa yang dikumpulkan, untuk apa, dan hak kamu.">
    @vite(['resources/css/app.css'])
<style>
.policy{line-height:1.7}.policy>*+*{margin-top:1rem}
.policy h1{font-size:1.9rem;font-weight:800;color:#163064}
.policy h2{margin-top:2.25rem;font-size:1.25rem;font-weight:700;color:#163064}
.policy ul{list-style:disc;padding-left:1.5rem}.policy li+li{margin-top:.4rem}
.policy a{color:#C75F00;font-weight:500;text-decoration:underline}
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
    <p class="text-sm text-gray-500">Diperbarui 6 November 2026. Berlaku untuk situs <b>temanseller.com</b>, akun TemanSeller, extension Chrome <b>Shofee</b>, aplikasi <b>Marketplace Audit</b>, dan layanan <b>Kelola Social Media</b>.</p>
    <p>TemanSeller menyediakan alat bantu untuk seller online. Halaman ini menjelaskan data apa yang kami kumpulkan, untuk apa kami memakainya, dan pilihan yang kamu miliki. Kami tidak menjual datamu.</p>

    <h2>1. Data yang kami kumpulkan</h2>
    <ul>
        <li><b>Informasi akun.</b> Saat kamu masuk dengan Google: ID akun Google, nama, alamat email, dan foto profil. Kami tidak menerima atau menyimpan kata sandi Google kamu.</li>
        <li><b>Informasi perangkat &amp; browser terhubung.</b> Nama browser dan sistem operasi, alamat IP, serta waktu terakhir aktif, untuk membatasi jumlah browser per akun dan menjaga keamanan.</li>
        <li><b>Status langganan &amp; pembayaran.</b> Paket, masa aktif trial atau langganan, nomor pesanan, nominal, dan status pembayaran. Pembayaran diproses oleh Midtrans; kami tidak menyimpan nomor kartu atau data rekening kamu.</li>
        <li><b>Data halaman marketplace yang kamu pindai (konten situs web).</b> Saat kamu memakai Shofee di halaman shopee.co.id, extension membaca data produk dan toko yang tampil di halaman itu, seperti nama, harga, stok, jumlah terjual, rating, dan lokasi toko. Data ini disimpan di perangkat kamu. Ringkasan angka produk (identitas produk, harga, jumlah terjual) dikirim ke server kami untuk menghitung tren 30 hari.</li>
        <li><b>Permintaan Analisa AI.</b> Ringkasan data produk atau toko yang kamu minta dianalisis dikirim ke server kami lalu diteruskan ke penyedia AI (kenari.id) untuk membuat analisa. Kami menyimpan hasilnya sementara (cache) dan jumlah pemakaian untuk menerapkan kuota harian.</li>
        <li><b>Log teknis.</b> Catatan kesalahan dan aktivitas server yang diperlukan untuk menjaga layanan tetap berjalan dan aman.</li>
        <li><b>Data audit Marketplace Audit.</b> Screenshot atau halaman tersimpan yang kamu unggah hanya dibaca sekali untuk mengambil angka dan teks yang tampil, lalu langsung dibuang (berkasnya tidak disimpan). Yang tersimpan hanya data hasil bacaan, tautan opsional, dan hasil audit. Kami tidak meminta atau menyimpan kata sandi, cookie, maupun sesi login Shopee-mu. Data audit dihapus otomatis setelah 24 jam.</li>
        <li><b>Data layanan Kelola Social Media.</b> Paket yang kamu pesan, catatan brand yang kamu isi, dan tautan folder Drive berisi hasil kerja. Akun Instagram dan Threads tetap milikmu.</li>
    </ul>

    <h2>2. Untuk apa data dipakai</h2>
    <ul>
        <li>Menjalankan fitur Shofee dan Marketplace Audit: menampilkan data, tren, skor audit, dan Analisa AI.</li>
        <li>Menjalankan layanan Kelola Social Media sesuai paket yang kamu pilih.</li>
        <li>Mengelola akun, trial, langganan, dan pembayaran kamu.</li>
        <li>Membatasi penyalahgunaan dan menjaga keamanan layanan.</li>
        <li>Menghubungi kamu terkait akun atau pembayaran bila diperlukan.</li>
    </ul>
    <p>Kami tidak memakai datamu untuk iklan, tidak menjualnya kepada pihak lain, dan tidak memakainya untuk menilai kelayakan kredit.</p>

    <h2>3. Penyimpanan di extension</h2>
    <p>Shofee menyimpan koleksi produk yang kamu pindai, preferensi, dan progres pemindaian di penyimpanan lokal browser (<code>chrome.storage.local</code>). Data ini tetap di perangkat kamu dan dapat dihapus kapan saja lewat tombol hapus di panel Shofee atau dengan mencopot extension. Izin <i>clipboardWrite</i> hanya dipakai saat kamu menekan tombol salin ke Google Sheets.</p>

    <h2>4. Pihak ketiga</h2>
    <ul>
        <li><b>Google</b>, untuk proses masuk (Sign in with Google).</li>
        <li><b>Midtrans</b>, untuk pemrosesan pembayaran.</li>
        <li><b>kenari.id</b>, penyedia model AI untuk fitur Analisa AI.</li>
    </ul>
    <p>Kami hanya membagikan data yang diperlukan agar layanan tersebut berjalan. Kami juga dapat mengungkapkan data bila diwajibkan oleh hukum yang berlaku.</p>

    <h2>5. Penyimpanan &amp; keamanan</h2>
    <p>Data akun disimpan di server kami dan dikirim melalui koneksi terenkripsi (HTTPS). Kami menyimpan data selama akunmu aktif atau selama diperlukan untuk menyediakan layanan dan memenuhi kewajiban hukum, lalu menghapusnya bila sudah tidak diperlukan.</p>

    <h2>6. Hakmu</h2>
    <p>Kamu dapat meminta akses, koreksi, atau penghapusan akun dan datamu, serta melepas browser yang terhubung kapan saja di <a href="{{ url('/akun') }}">halaman akun</a>. {!! $email ? 'Untuk permintaan penghapusan akun, hubungi kami di <a href="mailto:' . e($email) . '">' . e($email) . '</a>.' : '' !!}</p>

    <h2>7. Anak-anak</h2>
    <p>Layanan ini ditujukan untuk pelaku usaha dan tidak ditujukan bagi anak di bawah 18 tahun.</p>

    <h2>8. Perubahan kebijakan</h2>
    <p>Bila kebijakan ini berubah, versi terbaru akan ditampilkan di halaman ini beserta tanggal berlakunya.</p>

    <h2>9. Kontak</h2>
    <p>{!! $email ? 'Pertanyaan tentang privasi: <a href="mailto:' . e($email) . '">' . e($email) . '</a>.' : 'Pertanyaan tentang privasi dapat disampaikan melalui halaman akunmu.' !!}</p>
    <p class="text-xs text-gray-400">Shofee dan TemanSeller tidak berafiliasi dengan marketplace mana pun. Data yang ditampilkan bersifat estimasi dan bukan data resmi marketplace.</p>
</article>
</main>
<footer class="border-t border-gray-200 bg-white py-6 text-center text-xs text-gray-400">© {{ date('Y') }} TemanSeller</footer>
</body>
</html>
