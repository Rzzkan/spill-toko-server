<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Akun admin awal. Hanya dibuat bila belum ada (tidak menimpa kata sandi yang sudah diganti). */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $u = User::query()->firstOrNew(['username' => 'rzzkan']);
        if ($u->exists) return;
        $u->forceFill(['name' => 'Rzzkan', 'email' => 'rzzkan@gmail.com', 'password' => 'Delova@2026'])->save();
    }
}
