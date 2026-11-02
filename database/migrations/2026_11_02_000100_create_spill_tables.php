<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pelanggan extension (login Google). Admin dashboard memakai tabel `users` bawaan Laravel.
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('google_sub', 64)->unique();
            $t->string('email', 190)->index();
            $t->string('name', 190)->nullable();
            $t->string('picture', 500)->nullable();
            $t->dateTime('trial_ends_at');
            $t->dateTime('paid_until')->nullable();
            $t->boolean('lifetime')->default(false);
            $t->dateTime('last_login_at')->nullable();
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_id', 64)->unique();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->string('plan', 20);                       // monthly | lifetime
            $t->unsignedInteger('amount');                // Rupiah
            $t->string('status', 20)->default('pending')->index();   // pending|paid|expired|cancelled|denied|failed
            $t->string('snap_token', 100)->nullable();
            $t->string('payment_type', 40)->nullable();
            $t->text('raw_notification')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->timestamps();
        });

        Schema::create('admin_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $t->string('action', 40);
            $t->string('note', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 80)->primary();
            $t->mediumText('value')->nullable();
            $t->boolean('is_secret')->default(false);
            $t->timestamps();
        });

        Schema::create('ai_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->string('kind', 12);
            $t->boolean('cache_hit')->default(false);
            $t->string('model', 80)->nullable();
            $t->unsignedInteger('tokens_in')->default(0);
            $t->unsignedInteger('tokens_out')->default(0);
            $t->unsignedInteger('ms')->default(0);
            $t->string('status', 12)->default('ok');
            $t->string('error', 255)->nullable();
            $t->dateTime('created_at');
            $t->index(['customer_id', 'created_at']);
            $t->index('created_at');
        });

        Schema::create('ai_caches', function (Blueprint $t) {
            $t->string('hash', 64)->primary();
            $t->string('kind', 12);
            $t->string('model', 80)->nullable();
            $t->mediumText('result');
            $t->unsignedInteger('hits')->default(0);
            $t->dateTime('created_at');
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        foreach (['ai_caches', 'ai_logs', 'settings', 'admin_logs', 'orders', 'customers'] as $tbl) Schema::dropIfExists($tbl);
    }
};
