<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Isi awal "Layanan segera hadir" = Kelola TikTok (bisa diubah/dikosongkan di Pengaturan → Layanan Sosmed). */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('settings')->where('key', 'layanan.coming_soon')->exists()) {
            DB::table('settings')->insert(['key' => 'layanan.coming_soon', 'value' => 'Kelola TikTok', 'is_secret' => false, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'layanan.coming_soon')->delete();
    }
};
