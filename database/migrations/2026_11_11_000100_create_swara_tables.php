<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aplikasi "Swara": voice cloning (OmniVoice) jalan di Google Colab milik pengguna sendiri, antarmuka web di
 * temanseller.com. Server TIDAK pernah menerima audio (mekanisme 2: browser memanggil Colab langsung lewat tunnel).
 * Server hanya mengurus login, lisensi sekali beli, dan penerbitan token pendek (15 menit) yang diperiksa oleh
 * notebook Colab (tanda tangan Ed25519, kunci publik dilayani lewat /api/swara/pubkey).
 *
 * `swara_pairings`: 1 baris per pelanggan = sid Colab yang sedang aktif. Pairing baru menimpa yang lama, jadi
 * notebook/tab lama otomatis ditolak saat token-nya diperpanjang (lihat Swara::issueToken).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swara_pairings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete()->unique();
            $t->string('sid', 64);
            $t->timestamp('paired_at');
            $t->timestamps();
        });

        $now = now();
        if (!DB::table('apps')->where('slug', 'swara')->exists()) {
            DB::table('apps')->insert(['slug' => 'swara', 'name' => 'Swara', 'tagline' => 'Cloning suara sendiri, jalan di Google Colab kamu',
                'url' => '/swara', 'icon' => '🎙️', 'trial_days' => 0,
                'plans' => json_encode(['lifetime' => ['enabled' => false, 'price' => 0]]),
                'active' => true, 'sort' => (int) DB::table('apps')->max('sort') + 1, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('swara_pairings');
        DB::table('apps')->where('slug', 'swara')->delete();
    }
};
