<?php

namespace App\Support;

/** Daftar pengaturan yang tampil di dashboard (Pengaturan). type: text|url|number|bool|secret|model|textarea|select */
class SettingDefs
{
    public const GROUPS = [
        'langganan' => 'Langganan',
        'google' => 'Google Login',
        'midtrans' => 'Midtrans',
        'affiliate' => 'Affiliate Shopee',
        'ai' => 'AI (kenari.id)',
        'prompt' => 'Prompt AI',
        'ekstensi' => 'Extension',
        'layanan' => 'Layanan Sosmed',
        'audit' => 'Marketplace Audit',
    ];

    public static function all(): array
    {
        static $memo = null;
        return $memo ??= self::build();
    }

    private static function build(): array
    {
        return [
            // ---- langganan ----
            'license_ttl_hours'   => ['group' => 'langganan', 'label' => 'Batas offline extension (jam)', 'type' => 'number', 'default' => '72', 'min' => 1, 'help' => 'Extension harus terhubung ke server lagi paling lambat sekian jam.'],

            // ---- layanan ----
            'layanan.enabled'      => ['group' => 'layanan', 'label' => 'Tampilkan layanan di situs', 'type' => 'bool', 'default' => '1', 'help' => 'Matikan untuk menyembunyikan tab Layanan dan menutup pemesanan.'],
            'layanan.coming_soon'  => ['group' => 'layanan', 'label' => 'Layanan segera hadir', 'type' => 'text', 'default' => '', 'help' => 'Nama layanan yang akan datang, pisahkan dengan koma (mis. Kelola TikTok, Edit Video). Tampil di halaman Layanan (/layanan); kosongkan untuk menyembunyikan.'],
            'layanan.coming_soon_menu' => ['group' => 'layanan', 'label' => 'Segera hadir di menu Layanan', 'type' => 'text', 'default' => '', 'help' => 'Layanan yang tampil dengan label "Segera hadir" di dropdown menu Layanan (mis. Layanan Jasa Live Marketplace), pisahkan dengan koma. Juga ikut tampil di halaman Layanan; kosongkan untuk menyembunyikan.'],
            'layanan.wa_number'    => ['group' => 'layanan', 'label' => 'Nomor WhatsApp admin', 'type' => 'text', 'default' => '', 'help' => 'Format internasional tanpa + (contoh 6281234567890). Dipakai untuk tombol konsultasi & konfirmasi pesanan.'],
            'layanan.wa_template'  => ['group' => 'layanan', 'label' => 'Template pesan WhatsApp', 'type' => 'textarea', 'default' => "Halo TemanSeller, saya ingin konsultasi layanan kelola Instagram & Threads.", 'help' => 'Pesan awal saat calon pelanggan klik WhatsApp.'],
            'layanan.payment_info' => ['group' => 'layanan', 'label' => 'Info pembayaran', 'type' => 'textarea', 'default' => '', 'help' => 'Ditampilkan ke pelanggan setelah memesan (mis. rekening transfer). Pembayaran dikonfirmasi manual oleh admin.'],

            // ---- marketplace audit ----
            'audit.enabled'        => ['group' => 'audit', 'label' => 'Buka Marketplace Audit', 'type' => 'bool', 'default' => '1', 'help' => 'Matikan untuk menutup halaman dan pemindaian baru.'],
            'audit.free_scans'     => ['group' => 'audit', 'label' => 'Jatah scan gratis per bulan', 'type' => 'number', 'default' => '10', 'min' => 0, 'help' => 'Dihitung per kalender bulan (WIB), reset tanggal 1. Hanya scan yang berhasil dihitung.'],
            'audit.paid_daily_cap' => ['group' => 'audit', 'label' => 'Batas harian pelanggan berlangganan', 'type' => 'number', 'default' => '100', 'min' => 0, 'help' => '0 = tanpa batas. Pelindung biaya AI untuk paket "unlimited".'],
            'audit.retention_hours' => ['group' => 'audit', 'label' => 'Masa simpan hasil (jam)', 'type' => 'number', 'default' => '24', 'min' => 1, 'help' => 'Setelah ini tautan, data, dan hasil audit dihapus otomatis; hanya hitungan kuota yang tersisa.'],
            'audit.extension_url'  => ['group' => 'audit', 'label' => 'Tautan pasang extension', 'type' => 'url', 'default' => '', 'help' => 'Tautan Chrome Web Store extension yang membawa fitur Marketplace Audit. Ditampilkan di halaman audit.'],

            // ---- ekstensi ----
            'app.extension_id' => ['group' => 'ekstensi', 'label' => 'ID extension Chrome', 'type' => 'text', 'default' => 'ecngchlgljjgclfjhljppkmmoamdfpjl', 'help' => 'Dipakai untuk menampilkan Redirect URI Google yang benar. Ganti dengan ID dari Chrome Web Store setelah publish.'],

            // ---- google ----
            'google.client_id' => ['group' => 'google', 'label' => 'OAuth Client ID', 'type' => 'text', 'default' => '', 'help' => 'Dari Google Cloud Console → APIs & Services → Credentials → OAuth client ID (tipe Web application). Berakhiran .apps.googleusercontent.com.'],

            // ---- affiliate shopee (Open API resmi) ----
            'affiliate.enabled'     => ['group' => 'affiliate', 'label' => 'Aktifkan data Affiliate', 'type' => 'bool', 'default' => '0', 'help' => 'Bila aktif dan kredensial terisi, tab Affiliate di extension menampilkan komisi produk.'],
            'affiliate.app_id'      => ['group' => 'affiliate', 'label' => 'App ID', 'type' => 'text', 'default' => '', 'help' => 'Dari Shopee Affiliate → Open API (setelah pengajuan disetujui).'],
            'affiliate.secret'      => ['group' => 'affiliate', 'label' => 'Secret', 'type' => 'secret', 'default' => '', 'help' => 'Disimpan terenkripsi. Dipakai hanya untuk menandatangani permintaan dari server ini.'],
            'affiliate.endpoint'    => ['group' => 'affiliate', 'label' => 'Endpoint GraphQL', 'type' => 'url', 'default' => 'https://open-api.affiliate.shopee.co.id/graphql', 'help' => 'Bawaan untuk Indonesia. Ubah hanya bila Shopee memberi alamat lain.'],
            'affiliate.cache_hours' => ['group' => 'affiliate', 'label' => 'Simpan hasil (jam)', 'type' => 'number', 'default' => '12', 'min' => 1, 'help' => 'Hasil per produk disimpan agar tidak menghabiskan batas permintaan Shopee.'],
            // ---- midtrans ----
            'midtrans.production' => ['group' => 'midtrans', 'label' => 'Mode Production', 'type' => 'bool', 'default' => '0', 'help' => 'Matikan = Sandbox (uji coba). Server Key harus sesuai mode.'],
            'midtrans.server_key' => ['group' => 'midtrans', 'label' => 'Server Key', 'type' => 'secret', 'default' => '', 'help' => 'Sandbox: SB-Mid-server-… · Production: Mid-server-… (Midtrans → Settings → Access Keys).'],
            'midtrans.client_key' => ['group' => 'midtrans', 'label' => 'Client Key (opsional)', 'type' => 'text', 'default' => '', 'help' => 'Belum dipakai (pembayaran lewat halaman Snap redirect); disimpan untuk keperluan lanjutan.'],

            // ---- ai ----
            'ai.enabled'        => ['group' => 'ai', 'label' => 'Aktifkan analisis AI', 'type' => 'bool', 'default' => '1', 'help' => 'Matikan untuk menyembunyikan fitur AI di semua extension.'],
            'ai.auto'           => ['group' => 'ai', 'label' => 'Jalankan otomatis', 'type' => 'bool', 'default' => '1', 'help' => 'Extension langsung meminta analisis setelah data selesai di-scrape (hasil cache tidak memakai kuota).'],
            'ai.base_url'       => ['group' => 'ai', 'label' => 'Base URL', 'type' => 'url', 'default' => 'https://kenari.id/v1', 'help' => 'Endpoint kompatibel OpenAI.'],
            'ai.api_key'        => ['group' => 'ai', 'label' => 'API key kenari.id', 'type' => 'secret', 'default' => '', 'help' => 'Format kn-… (dashboard kenari.id → API keys).'],
            'ai.model'          => ['group' => 'ai', 'label' => 'Model utama', 'type' => 'model', 'default' => 'deepseek-v4-flash', 'help' => 'ID model kenari. Klik "Muat daftar model" untuk memilih.'],
            'ai.fallback_model' => ['group' => 'ai', 'label' => 'Model cadangan', 'type' => 'model', 'default' => '', 'help' => 'Dipakai bila model utama gagal (opsional).'],
            'ai.temperature'    => ['group' => 'ai', 'label' => 'Temperature', 'type' => 'text', 'default' => '0.3', 'help' => '0–2. Rendah = lebih konsisten.'],
            'ai.max_tokens'     => ['group' => 'ai', 'label' => 'Maks token jawaban', 'type' => 'number', 'default' => '2200', 'min' => 300],
            'ai.json_mode'      => ['group' => 'ai', 'label' => 'Paksa mode JSON', 'type' => 'bool', 'default' => '1', 'help' => 'Kirim response_format=json_object; otomatis diulang tanpa itu bila model menolak.'],
            'ai.timeout'        => ['group' => 'ai', 'label' => 'Timeout (detik)', 'type' => 'number', 'default' => '70', 'min' => 10],
            'ai.cache_hours'    => ['group' => 'ai', 'label' => 'Cache hasil (jam)', 'type' => 'number', 'default' => '24', 'help' => 'Data identik tidak dianalisis ulang. 0 = matikan cache.'],
            'ai.max_items'      => ['group' => 'ai', 'label' => 'Maks produk dikirim ke AI', 'type' => 'number', 'default' => '40', 'min' => 5, 'help' => 'Membatasi ukuran prompt (5–80).'],
            'ai.quota.trial'    => ['group' => 'ai', 'label' => 'Kuota/hari – Trial', 'type' => 'number', 'default' => '20', 'help' => '0 = tanpa batas. Hanya analisis baru yang dihitung (bukan cache). Reset 00:00 WIB.'],
            'ai.quota.monthly'  => ['group' => 'ai', 'label' => 'Kuota/hari – Bulanan', 'type' => 'number', 'default' => '100'],
            'ai.quota.yearly'   => ['group' => 'ai', 'label' => 'Kuota/hari – Tahunan', 'type' => 'number', 'default' => '150', 'help' => '0 = tanpa batas.'],
            'ai.quota.lifetime' => ['group' => 'ai', 'label' => 'Kuota/hari – Lifetime', 'type' => 'number', 'default' => '100'],
            'ai.rate_per_min'   => ['group' => 'ai', 'label' => 'Maks analisis/menit per pengguna', 'type' => 'number', 'default' => '6', 'help' => '0 = tanpa batas.'],

            // ---- prompt ----
            'ai.prompt.system'  => ['group' => 'prompt', 'label' => 'Prompt sistem (peran & aturan)', 'type' => 'textarea', 'default' => ''],
            'ai.prompt.product' => ['group' => 'prompt', 'label' => 'Instruksi: halaman produk', 'type' => 'textarea', 'default' => ''],
            'ai.prompt.shop'    => ['group' => 'prompt', 'label' => 'Instruksi: halaman toko', 'type' => 'textarea', 'default' => ''],
            'ai.prompt.search'  => ['group' => 'prompt', 'label' => 'Instruksi: halaman pencarian', 'type' => 'textarea', 'default' => ''],
        ];
    }

    public static function group(string $group): array
    {
        return array_filter(self::all(), fn ($d) => $d['group'] === $group);
    }
}
