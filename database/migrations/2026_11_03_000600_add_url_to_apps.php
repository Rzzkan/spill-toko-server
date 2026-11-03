<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Alamat halaman tiap aplikasi (tujuan tombol di landing TemanSeller), diatur dari admin. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $t) { $t->string('url', 255)->nullable()->after('tagline'); });
        \Illuminate\Support\Facades\DB::table('apps')->where('slug', 'shofee')->whereNull('url')->update(['url' => '/shofee']);
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $t) { $t->dropColumn('url'); });
    }
};
