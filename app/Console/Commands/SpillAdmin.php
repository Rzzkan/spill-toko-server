<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SpillAdmin extends Command
{
    protected $signature = 'spill:admin {email : Email admin} {--name=Admin : Nama} {--username= : Username login (default: bagian email sebelum @)} {--password= : Kata sandi (kosong = dibuat acak)}';
    protected $description = 'Buat admin baru atau setel ulang kata sandi admin yang ada';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->error('Email tidak valid.'); return self::FAILURE; }
        $pass = (string) ($this->option('password') ?: Str::password(14, symbols: false));
        if (strlen($pass) < 8) { $this->error('Kata sandi minimal 8 karakter.'); return self::FAILURE; }
        $u = User::query()->firstOrNew(['email' => $email]);
        $new = !$u->exists;
        $u->name = $new ? (string) $this->option('name') : $u->name;
        if ($this->option('username') || !$u->username) $u->username = (string) ($this->option('username') ?: Str::before($email, '@'));
        $u->password = $pass;
        $u->save();
        $this->info(($new ? 'Admin dibuat' : 'Kata sandi diganti') . ": $email");
        if (!$this->option('password')) $this->line("Kata sandi: $pass");
        return self::SUCCESS;
    }
}
