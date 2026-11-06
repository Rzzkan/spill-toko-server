<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aplikasi "Marketplace Audit": audit toko & produk Shopee. Data dikumpulkan di browser pengguna oleh extension,
 * dianalisis AI, lalu ditampilkan. Isi scan (url/data/hasil) dihapus setelah masa simpan; baris tetap ada hanya sebagai penghitung kuota.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_scans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->char('token_hash', 64);                          // token sekali pakai untuk extension (hash)
            $t->string('kind', 10);                              // shop|product
            $t->string('url', 500)->nullable();                  // dikosongkan saat dibersihkan
            $t->string('status', 12)->default('waiting');        // waiting|analyzing|done|failed|cancelled
            $t->string('error', 255)->nullable();
            $t->boolean('counted')->default(false);              // true = memakai jatah scan
            $t->longText('data')->nullable();                    // data mentah dari extension; dihapus segera setelah analisis
            $t->longText('result')->nullable();                  // hasil audit; dihapus setelah masa simpan
            $t->timestamp('purged_at')->nullable();
            $t->timestamps();
            $t->index(['customer_id', 'counted', 'created_at']);
            $t->index(['status', 'created_at']);
        });

        // Aplikasi baru: tanpa trial (jatah gratis diatur di Pengaturan → Audit); paket berbayar = harga 0 (belum dijual) sampai admin mengisi.
        $now = now();
        if (!DB::table('apps')->where('slug', 'audit')->exists()) {
            DB::table('apps')->insert(['slug' => 'audit', 'name' => 'Marketplace Audit', 'tagline' => 'Audit toko & produk Shopee dibantu AI', 'url' => '/marketplace-audit', 'icon' => '🩺',
                'trial_days' => 0, 'plans' => json_encode(['monthly' => ['price' => 0, 'days' => 30], 'yearly' => ['price' => 0, 'days' => 365]]),
                'active' => true, 'sort' => (int) DB::table('apps')->max('sort') + 1, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_scans');
        DB::table('apps')->where('slug', 'audit')->delete();
    }
};
