<?php

use App\Services\Settings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Link unduh/pasang tiap aplikasi (mis. Chrome Web Store untuk Shofee), diatur dari admin. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $t) { $t->string('download_url', 255)->nullable()->after('url'); });
        $old = trim((string) Settings::get('app.chrome_store_url', ''));
        DB::table('apps')->where('slug', 'shofee')->update(['download_url' => $old !== '' ? $old : 'https://chromewebstore.google.com/detail/nnjdmcbildnombgpnoolgopbekipegpa']);
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $t) { $t->dropColumn('download_url'); });
    }
};
