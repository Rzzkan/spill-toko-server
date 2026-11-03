<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // riwayat order per pengguna per aplikasi (halaman akun, detail aplikasi, admin)
        Schema::table('orders', function (Blueprint $t) { $t->index(['customer_id', 'app'], 'orders_customer_app_index'); });
        // filter status langganan di admin / dashboard
        Schema::table('subscriptions', function (Blueprint $t) { $t->index(['app', 'lifetime', 'paid_until'], 'subscriptions_app_status_index'); });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) { $t->dropIndex('orders_customer_app_index'); });
        Schema::table('subscriptions', function (Blueprint $t) { $t->dropIndex('subscriptions_app_status_index'); });
    }
};
