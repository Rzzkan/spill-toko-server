<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Layanan "Kelola Instagram & Threads": jenis paket, paket harga, portofolio, dan langganan layanan per pelanggan.
 * Semua diatur dari admin. Paket awal hanya DRAF nonaktif (harga 0) — admin yang mengisi harga lalu mengaktifkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 40)->unique();
            $t->string('name', 80);
            $t->string('description', 255)->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('service_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_type_id')->constrained('service_types')->cascadeOnDelete();
            $t->string('name', 80);
            $t->string('tagline', 160)->nullable();
            $t->unsignedBigInteger('price')->default(0);          // rupiah
            $t->string('period', 30)->default('/bulan');          // teks setelah harga
            $t->text('features')->nullable();                     // satu fitur per baris
            $t->string('badge', 30)->nullable();                  // mis. "Paling laris"
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_active')->default(false);
            $t->unsignedInteger('duration_days')->default(30);    // lama langganan saat diaktifkan
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('service_portfolios', function (Blueprint $t) {
            $t->id();
            $t->string('title', 120);
            $t->string('client_name', 120)->nullable();
            $t->string('platform', 40)->nullable();               // Instagram / Threads / keduanya
            $t->text('description')->nullable();
            $t->string('result_text', 160)->nullable();           // mis. "Followers +2.4rb dalam 3 bulan"
            $t->string('image_path', 255)->nullable();            // di disk lokal (storage/app/private/layanan)
            $t->string('link_url', 255)->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('service_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->foreignId('service_plan_id')->nullable()->constrained('service_plans')->nullOnDelete();
            $t->string('plan_name', 120);                         // salinan saat memesan
            $t->unsignedBigInteger('price')->default(0);
            $t->string('status', 20)->default('pending');         // pending|active|expired|cancelled
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->string('drive_url', 500)->nullable();             // folder Drive pelanggan, diisi admin
            $t->text('customer_note')->nullable();                // catatan dari pelanggan (akun/brand)
            $t->text('admin_note')->nullable();
            $t->timestamps();
            $t->index(['customer_id', 'status']);
        });

        $now = now();
        $types = [
            ['slug' => 'instagram', 'name' => 'Instagram', 'description' => 'Kelola konten & jadwal posting Instagram.', 'sort' => 1],
            ['slug' => 'threads', 'name' => 'Threads', 'description' => 'Kelola posting & balasan Threads.', 'sort' => 2],
            ['slug' => 'combo', 'name' => 'Instagram + Threads', 'description' => 'Dua platform dikelola sekaligus.', 'sort' => 3],
        ];
        foreach ($types as $x) DB::table('service_types')->insert($x + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $combo = DB::table('service_types')->where('slug', 'combo')->value('id');
        $drafts = [
            ['Starter', 'Mulai rutin posting', "Konten terjadwal\nCaption + hashtag\nLaporan bulanan"],
            ['Growth', 'Untuk toko yang mau tumbuh', "Semua di Starter\nDesain visual konten\nBalas komentar & DM dasar"],
            ['Pro', 'Dikelola penuh', "Semua di Growth\nRiset tren & kompetitor\nPrioritas revisi"],
        ];
        foreach ($drafts as $i => [$n, $tag, $f]) {
            DB::table('service_plans')->insert(['service_type_id' => $combo, 'name' => $n, 'tagline' => $tag, 'price' => 0, 'period' => '/bulan', 'features' => $f,
                'is_featured' => $n === 'Growth', 'is_active' => false, 'duration_days' => 30, 'sort' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_subscriptions');
        Schema::dropIfExists('service_portfolios');
        Schema::dropIfExists('service_plans');
        Schema::dropIfExists('service_types');
    }
};
