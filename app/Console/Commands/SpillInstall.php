<?php

namespace App\Console\Commands;

use App\Services\License;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SpillInstall extends Command
{
    protected $signature = 'spill:install {--admin= : Email admin pertama}';
    protected $description = 'Migrasi database, buat kunci lisensi/JWT, dan (opsional) admin pertama';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);
        $pub = License::publicKey();   // membuat kunci lisensi & JWT bila belum ada
        $this->info('Kunci lisensi siap.');
        $this->line("LICENSE_PUBKEY = $pub");
        if ($email = $this->option('admin')) $this->call('spill:admin', ['email' => $email]);
        $this->newLine();
        $this->line('Selesai. Buka /admin lalu isi Pengaturan → Google, Midtrans, AI (kenari.id).');
        return self::SUCCESS;
    }
}
