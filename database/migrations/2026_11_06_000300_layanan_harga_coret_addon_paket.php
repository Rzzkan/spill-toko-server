<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Harga coret & add-on pada paket layanan, plus paket awal Instagram/Threads (hanya bila belum ada paket berharga). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_plans', function (Blueprint $t) {
            $t->unsignedBigInteger('original_price')->default(0)->after('price');   // harga sebelum diskon (dicoret); 0 = tidak ada
            $t->string('addon', 200)->nullable()->after('features');
        });

        if (DB::table('service_plans')->where('price', '>', 0)->exists()) return;   // admin sudah mengisi paket sendiri: jangan timpa
        DB::table('service_plans')->delete();
        DB::table('service_types')->whereIn('slug', ['instagram', 'threads', 'combo'])->delete();

        $now = now();
        $ig = ['Hasil riset visual desain', 'Hasil riset brand voice'];
        $th = ['Hasil riset brand voice'];
        $types = [
            ['ig-tanpa-posting', 'IG Tanpa Posting', 'Konten Instagram siap pakai, kamu yang posting sendiri.', [
                ['Coba Sendiri', 500000, 1000000, 'sekali bayar', 30, array_merge($ig, ['30 konten Feed (mix single & carousel)', '30 konten Story', 'Caption Instagram']), null, false],
            ]],
            ['ig-dibantu-posting', 'IG Dibantu Posting', 'Konten Instagram siap pakai dan kami bantu postingkan.', [
                ['Mulai Dulu', 750000, 1500000, '/ 1 bulan', 30, array_merge($ig, ['30 konten Feed (mix single & carousel)', '30 konten Story', 'Caption Instagram', 'Dibantu posting selama 1 bulan']), 'Tambah desain Rp10.000 / desain (sesuai permintaan)', false],
                ['Naik Kelas', 2000000, 4000000, '/ 3 bulan', 90, array_merge($ig, ['90 konten Feed (mix single & carousel)', '90 konten Story', 'Caption Instagram', 'Dibantu posting selama 3 bulan']), 'Tambah visual desain Rp10.000 / desain (sesuai permintaan)', true],
                ['Cuan Maksimal', 3750000, 7500000, '/ 6 bulan', 180, array_merge($ig, ['180 konten Feed (mix single & carousel)', '180 konten Story', 'Caption Instagram', 'Dibantu posting selama 6 bulan']), 'Tambah visual desain Rp10.000 / desain (sesuai permintaan)', false],
            ]],
            ['threads-tanpa-posting', 'Threads Tanpa Posting', 'Ide utas Threads siap pakai, kamu yang posting sendiri.', [
                ['Coba Sendiri', 20000, 100000, 'sekali bayar', 30, array_merge($th, ['100 ide utas Threads']), null, false],
            ]],
            ['threads-dibantu-posting', 'Threads Dibantu Posting', 'Ide utas Threads dan kami bantu postingkan.', [
                ['Mulai Dulu', 50000, 200000, '/ 1 bulan', 30, array_merge($th, ['120 ide utas Threads', 'Dibantu posting selama 1 bulan']), 'Tambah visual desain Rp10.000 / desain (sesuai permintaan)', false],
                ['Naik Kelas', 200000, 550000, '/ 3 bulan', 90, array_merge($th, ['360 ide utas Threads', 'Dibantu posting selama 3 bulan']), 'Tambah visual desain Rp10.000 / desain (sesuai permintaan)', true],
                ['Cuan Maksimal', 500000, 1000000, '/ 6 bulan', 180, array_merge($th, ['720 ide utas Threads', 'Dibantu posting selama 6 bulan']), 'Tambah visual desain Rp10.000 / desain (sesuai permintaan)', false],
            ]],
        ];
        foreach ($types as $i => [$slug, $name, $desc, $plans]) {
            $id = DB::table('service_types')->insertGetId(['slug' => $slug, 'name' => $name, 'description' => $desc, 'sort' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            foreach ($plans as $j => [$pn, $price, $orig, $period, $days, $features, $addon, $featured]) {
                $help = str_contains($name, 'Tanpa') ? 'Hasil dikirim lewat Drive, kamu yang posting' : 'Hasil dikirim lewat Drive dan kami bantu posting';
                DB::table('service_plans')->insert(['service_type_id' => $id, 'name' => $pn, 'tagline' => $help, 'price' => $price, 'original_price' => $orig, 'period' => $period,
                    'features' => implode("\n", $features), 'addon' => $addon, 'badge' => null, 'is_featured' => $featured, 'is_active' => true, 'duration_days' => $days, 'sort' => $j + 1,
                    'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('service_plans', function (Blueprint $t) { $t->dropColumn(['original_price', 'addon']); });
    }
};
