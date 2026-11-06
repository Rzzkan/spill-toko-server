<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Isi awal "Segera hadir di menu" = Layanan Jasa Live Marketplace (bisa diubah/dikosongkan di Pengaturan → Layanan Sosmed). */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('settings')->where('key', 'layanan.coming_soon_menu')->exists()) {
            DB::table('settings')->insert(['key' => 'layanan.coming_soon_menu', 'value' => 'Layanan Jasa Live Marketplace', 'is_secret' => false, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'layanan.coming_soon_menu')->delete();
    }
};
