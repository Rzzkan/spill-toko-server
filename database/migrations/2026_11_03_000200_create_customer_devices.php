<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_devices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('device_hash', 64);          // sha256 dari ID browser yang dibuat extension
            $t->string('label', 120)->nullable();   // mis. "Chrome · Windows"
            $t->string('ip', 45)->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->timestamps();
            $t->unique(['customer_id', 'device_hash']);
        });
    }

    public function down(): void { Schema::dropIfExists('customer_devices'); }
};
