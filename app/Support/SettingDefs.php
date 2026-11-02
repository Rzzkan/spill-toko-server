<?php

namespace App\Support;

/** Daftar pengaturan yang tampil di dashboard (Pengaturan). type: text|url|number|bool|secret|model|textarea|select */
class SettingDefs
{
    public const GROUPS = [
        'langganan' => 'Langganan',
        'google' => 'Google Login',
        'midtrans' => 'Midtrans',
        'ai' => 'AI (kenari.id)',
        'prompt' => 'Prompt AI',
        'ekstensi' => 'Extension',
    ];

    public static function all(): array
    {
        return [
            // ---- langganan ----
            'trial_days'          => ['group' => 'langganan', 'label' => 'Lama trial (hari)', 'type' => 'number', 'default' => '3', 'help' => 'Berlaku untuk akun baru. Isi 0 untuk tanpa trial.'],
            'plan.monthly.price'  => ['group' => 'langganan', 'label' => 'Harga Bulanan (Rp)', 'type' => 'number', 'default' => '24000', 'min' => 1000, 'help' => 'Harga yang benar-benar ditagih.'],
            'plan.monthly.original' => ['group' => 'langganan', 'label' => 'Harga coret Bulanan (Rp)', 'type' => 'number', 'default' => '49000', 'help' => 'Hanya tampilan (dicoret). 0 = tidak ditampilkan.'],
            'plan.monthly.days'   => ['group' => 'langganan', 'label' => 'Masa aktif Bulanan (hari)', 'type' => 'number', 'default' => '30', 'min' => 1],
            'plan.yearly.price'   => ['group' => 'langganan', 'label' => 'Harga Tahunan (Rp)', 'type' => 'number', 'default' => '249000', 'min' => 1000],
            'plan.yearly.original' => ['group' => 'langganan', 'label' => 'Harga coret Tahunan (Rp)', 'type' => 'number', 'default' => '549000', 'help' => 'Hanya tampilan (dicoret). 0 = tidak ditampilkan.'],
            'plan.yearly.days'    => ['group' => 'langganan', 'label' => 'Masa aktif Tahunan (hari)', 'type' => 'number', 'default' => '365', 'min' => 1],
            'plan.lifetime.enabled' => ['group' => 'langganan', 'label' => 'Jual paket Lifetime', 'type' => 'bool', 'default' => '0', 'help' => 'Opsional. Pengguna Lifetime lama tetap aktif walau dimatikan; admin tetap bisa memberi Lifetime manual.'],
            'plan.lifetime.price' => ['group' => 'langganan', 'label' => 'Harga Lifetime (Rp)', 'type' => 'number', 'default' => '200000', 'min' => 1000, 'help' => 'Dipakai bila paket Lifetime dijual.'],
            'license_ttl_hours'   => ['group' => 'langganan', 'label' => 'Batas offline extension (jam)', 'type' => 'number', 'default' => '72', 'min' => 1, 'help' => 'Extension harus terhubung ke server lagi paling lambat sekian jam.'],
            'app.chrome_store_url' => ['group' => 'langganan', 'label' => 'Link Chrome Web Store', 'type' => 'url', 'default' => '', 'help' => 'Dipakai tombol "Pasang extension" di halaman depan.'],

            // ---- ekstensi ----
            'app.extension_id' => ['group' => 'ekstensi', 'label' => 'ID extension Chrome', 'type' => 'text', 'default' => 'ecngchlgljjgclfjhljppkmmoamdfpjl', 'help' => 'Dipakai untuk menampilkan Redirect URI Google yang benar. Ganti dengan ID dari Chrome Web Store setelah publish.'],

            // ---- google ----
            'google.client_id' => ['group' => 'google', 'label' => 'OAuth Client ID', 'type' => 'text', 'default' => '', 'help' => 'Dari Google Cloud Console → APIs & Services → Credentials → OAuth client ID (tipe Web application). Berakhiran .apps.googleusercontent.com.'],

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
