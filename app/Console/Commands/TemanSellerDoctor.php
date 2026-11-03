<?php

namespace App\Console\Commands;

use App\Services\License;
use App\Services\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TemanSellerDoctor extends Command
{
    protected $signature = 'temanseller:doctor';
    protected $aliases = ['spill:doctor'];   // nama lama tetap bisa dipakai
    protected $description = 'Periksa kesiapan server (PHP, ekstensi, DB, tabel, izin folder, kunci)';

    public function handle(): int
    {
        $bad = 0;
        $chk = function (string $name, callable $fn) use (&$bad) {
            try { $r = $fn(); $ok = $r === true || is_string($r); $this->line(($ok ? '  [OK]   ' : '  [GAGAL] ') . $name . (is_string($r) ? " — $r" : '')); if (!$ok) $bad++; }
            catch (\Throwable $e) { $bad++; $this->line("  [GAGAL] $name — " . $e->getMessage()); }
        };
        $this->info('TemanSeller — pemeriksaan server');
        $chk('PHP >= 8.2', fn () => version_compare(PHP_VERSION, '8.2.0', '>=') ? PHP_VERSION : false);
        foreach (['openssl', 'mbstring', 'pdo_mysql', 'curl', 'json', 'fileinfo', 'ctype', 'tokenizer', 'xml'] as $e) $chk("ekstensi $e", fn () => extension_loaded($e));
        $chk('APP_KEY terisi', fn () => (bool) config('app.key'));
        $chk('Koneksi database', fn () => DB::connection()->getDatabaseName());
        foreach (['users', 'customers', 'orders', 'settings', 'sessions', 'cache', 'ai_logs', 'ai_caches', 'admin_logs'] as $t) $chk("tabel $t", fn () => Schema::hasTable($t));
        $chk('kolom customers.paid_plan', fn () => Schema::hasColumn('customers', 'paid_plan'));
        $chk('storage dapat ditulis', fn () => is_writable(storage_path('logs')) && is_writable(storage_path('framework/views')));
        $chk('bootstrap/cache dapat ditulis', fn () => is_writable(base_path('bootstrap/cache')));
        $chk('public/build/manifest.json', fn () => is_file(public_path('build/manifest.json')));
        // sodium native opsional: ada fallback paragonie/sodium_compat (lebih lambat tapi berfungsi)
        $this->line(extension_loaded('sodium') ? '  [OK]   ekstensi sodium' : '  [INFO] ekstensi sodium tidak aktif — memakai sodium_compat (normal, tidak perlu diperbaiki)');
        $chk('tulis & baca Settings', function () { Settings::set('app.doctor', 'ok'); Settings::flush(); return Settings::get('app.doctor') === 'ok'; });
        $chk('kunci lisensi', fn () => substr(License::publicKey(), 0, 12) . '…');
        $this->line($bad ? "\n$bad masalah ditemukan. Perbaiki yang [GAGAL], lalu cek storage/logs/laravel.log." : "\nSemua siap.");
        return $bad ? self::FAILURE : self::SUCCESS;
    }
}
