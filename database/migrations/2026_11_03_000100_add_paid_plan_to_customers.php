<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->string('paid_plan', 20)->nullable()->after('paid_until');   // paket berbayar terakhir: monthly | yearly
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) { $t->dropColumn('paid_plan'); });
    }
};
