<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daftar aplikasi TemanSeller (Shofee, dan aplikasi lain ke depannya)
        Schema::create('apps', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 30)->unique();
            $t->string('name', 80);
            $t->string('tagline', 200)->nullable();
            $t->string('icon', 16)->nullable();          // emoji
            $t->unsignedSmallInteger('trial_days')->default(3);
            $t->json('plans')->nullable();               // {monthly:{price,original,days}, yearly:{...}, lifetime:{enabled,price}}; null = pakai Pengaturan (Shofee)
            $t->boolean('active')->default(true);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        // Langganan per pelanggan per aplikasi (trial & masa aktif terpisah)
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->string('app', 30);
            $t->dateTime('trial_ends_at')->nullable();
            $t->dateTime('paid_until')->nullable();
            $t->string('paid_plan', 20)->nullable();
            $t->boolean('lifetime')->default(false);
            $t->timestamps();
            $t->unique(['customer_id', 'app']);
            $t->index('app');
        });

        Schema::table('orders', function (Blueprint $t) {
            $t->string('app', 30)->default('shofee')->after('customer_id')->index();
        });

        $now = now();
        DB::table('apps')->insert(['slug' => 'shofee', 'name' => 'Shofee', 'tagline' => 'Riset produk & toko kompetitor di marketplace', 'icon' => '🔍',
            'trial_days' => 3, 'plans' => null, 'active' => true, 'sort' => 1, 'created_at' => $now, 'updated_at' => $now]);

        // pelanggan lama -> langganan Shofee (salin status trial / bayar / lifetime)
        DB::table('customers')->orderBy('id')->chunkById(500, function ($rows) use ($now) {
            DB::table('subscriptions')->insert($rows->map(fn ($c) => [
                'customer_id' => $c->id, 'app' => 'shofee', 'trial_ends_at' => $c->trial_ends_at, 'paid_until' => $c->paid_until,
                'paid_plan' => $c->paid_plan ?? null, 'lifetime' => (bool) $c->lifetime, 'created_at' => $now, 'updated_at' => $now,
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) { $t->dropIndex(['app']); $t->dropColumn('app'); });
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('apps');
    }
};
