<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat harian per produk (dikumpulkan dari semua pengguna) untuk menghitung trend naik/turun 30 hari.
        Schema::create('item_snapshots', function (Blueprint $t) {
            $t->id();
            $t->string('item_key', 40);        // shopId.itemId
            $t->date('day');
            $t->unsignedBigInteger('sold')->nullable();   // total terjual (pasti dari API)
            $t->unsignedInteger('m30')->nullable();       // terjual 30 hari (pasti dari API)
            $t->unsignedBigInteger('price')->nullable();
            $t->timestamps();
            $t->unique(['item_key', 'day']);
        });
    }

    public function down(): void { Schema::dropIfExists('item_snapshots'); }
};
