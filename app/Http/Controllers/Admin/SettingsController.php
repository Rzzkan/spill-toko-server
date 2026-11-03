<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Services\Ai;
use App\Services\AiPrompts;
use App\Services\License;
use App\Services\Midtrans;
use App\Services\Settings;
use App\Services\ShopeeAffiliate;
use App\Support\SettingDefs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    public function show(string $group = 'langganan')
    {
        abort_unless(isset(SettingDefs::GROUPS[$group]), 404);
        $fields = [];
        foreach (SettingDefs::group($group) as $key => $d) {
            $default = $d['default'];
            if ($group === 'prompt') {
                $kind = substr($key, strlen('ai.prompt.'));
                $default = $kind === 'system' ? AiPrompts::defaultSystem() : AiPrompts::defaultKind($kind);
            }
            $d['key'] = $key;
            $d['default_text'] = $default;
            $d['value'] = $d['type'] === 'secret' ? '' : (string) (Settings::raw($key) ?? $default);
            $d['is_set'] = Settings::has($key);
            $fields[] = $d;
        }
        try {
            $pubkey = License::publicKey();
        } catch (\Throwable $e) {
            report($e);
            $pubkey = '(gagal membuat kunci: ' . $e->getMessage() . ')';
        }
        $ext = [
            'api_base' => url('/api/index.php'),
            'pubkey' => $pubkey,
            'ext_id' => (string) Settings::get('app.extension_id'),
        ];
        return view('admin.settings', ['group' => $group, 'groups' => SettingDefs::GROUPS, 'fields' => $fields, 'ext' => $ext,
            'webhook' => url('/api/index.php') . '?r=midtrans/notify', 'finish' => url('/thanks'),
            'origin' => rtrim(url('/'), '/'), 'redirect' => 'https://' . Settings::get('app.extension_id') . '.chromiumapp.org/']);
    }

    public function update(Request $r, string $group)
    {
        abort_unless(isset(SettingDefs::GROUPS[$group]), 404);
        $errors = [];
        $changed = [];
        foreach (SettingDefs::group($group) as $key => $d) {
            $in = $r->input('s.' . str_replace('.', '__', $key));
            $type = $d['type'];
            if ($type === 'bool') {
                $new = $r->boolean('s.' . str_replace('.', '__', $key)) ? '1' : '0';
                if ((string) Settings::get($key) !== $new) { Settings::set($key, $new); $changed[] = $key; }
                continue;
            }
            if ($type === 'secret') {
                if ($r->boolean('clear.' . str_replace('.', '__', $key))) { Settings::forget($key); $changed[] = $key; }
                elseif (is_string($in) && trim($in) !== '') { Settings::set($key, trim($in)); $changed[] = $key; }
                continue;
            }
            $v = is_string($in) ? trim($in) : '';
            if ($type === 'number' && $v !== '') {
                if (!ctype_digit($v) || (int) $v < ($d['min'] ?? 0)) { $errors[] = $d['label'] . ' harus angka ≥ ' . ($d['min'] ?? 0) . '.'; continue; }
            }
            if ($key === 'ai.temperature' && $v !== '' && (!is_numeric($v) || $v < 0 || $v > 2)) { $errors[] = 'Temperature harus 0–2.'; continue; }
            if ($type === 'url' && $v !== '' && !filter_var($v, FILTER_VALIDATE_URL)) { $errors[] = $d['label'] . ' bukan URL yang valid.'; continue; }
            if ($key === 'google.client_id' && $v !== '' && !preg_match('/^[\w\-]+\.apps\.googleusercontent\.com$/', $v)) { $errors[] = 'Client ID Google harus berakhiran .apps.googleusercontent.com.'; continue; }
            if ($key === 'ai.base_url') $v = rtrim($v, '/');
            // nilai kosong / sama dengan bawaan -> hapus agar bawaan berlaku (prompt ikut terbarui bila bawaan di-update)
            $default = $d['default'];
            if ($group === 'prompt') { $k = substr($key, strlen('ai.prompt.')); $default = $k === 'system' ? AiPrompts::defaultSystem() : AiPrompts::defaultKind($k); }
            $same = $v === '' || str_replace("\r\n", "\n", $v) === str_replace("\r\n", "\n", (string) $default);
            if ($same) { if (Settings::raw($key) !== null) { Settings::forget($key); $changed[] = $key; } }
            elseif (Settings::raw($key) !== $v) { Settings::set($key, $v); $changed[] = $key; }
        }
        if ($errors) return back()->withInput()->with('error', implode(' ', $errors));
        if ($changed) {
            AdminLog::query()->create(['user_id' => $r->user()->id, 'action' => 'settings', 'note' => $group . ': ' . implode(', ', $changed)]);   // nama kunci saja, tanpa nilai
        }
        return redirect()->route('admin.settings', $group)->with('ok', $changed ? 'Pengaturan disimpan.' : 'Tidak ada perubahan.');
    }

    /** Tombol tes: nilai yang dipakai adalah yang sudah tersimpan. */
    public function test(string $what): JsonResponse
    {
        try {
            $res = match ($what) {
                'midtrans' => Midtrans::test(),
                'affiliate' => ShopeeAffiliate::test(),
                'ai' => Ai::test(),
                'models' => (function () { [$ok, $d] = Ai::fetchModels(); return [$ok, $ok ? count($d) . ' model ditemukan' : $d, $ok ? $d : null]; })(),
                'google' => $this->testGoogle(),
                default => abort(404),
            };
        } catch (\Throwable $e) {
            report($e);
            $res = [false, 'Galat: ' . $e->getMessage()];
        }
        return response()->json(['ok' => $res[0], 'message' => $res[1], 'models' => $res[2] ?? null]);
    }

    private function testGoogle(): array
    {
        $id = (string) Settings::get('google.client_id', '');
        if ($id === '') return [false, 'Client ID belum diisi.'];
        if (!preg_match('/^[\w\-]+\.apps\.googleusercontent\.com$/', $id)) return [false, 'Format Client ID tidak valid.'];
        try {
            $r = Http::acceptJson()->timeout(10)->get(config('spill.google_tokeninfo_url'), ['id_token' => 'tes']);
        } catch (\Throwable) { return [false, 'Server tidak bisa menghubungi Google.']; }
        return $r->status() === 400 ? [true, 'Format Client ID benar & server bisa menjangkau Google ✓ (login sebenarnya diuji dari extension).'] : [false, 'Respons Google tak terduga (HTTP ' . $r->status() . ')'];
    }

    public function regenerateKey(Request $r)
    {
        if (trim((string) $r->input('confirm')) !== 'GANTI KUNCI') return back()->with('error', 'Ketik GANTI KUNCI persis untuk konfirmasi.');
        License::regenerate();
        AdminLog::query()->create(['user_id' => $r->user()->id, 'action' => 'regenerate_license_key', 'note' => 'Kunci lisensi diganti']);
        return back()->with('ok', 'Kunci lisensi baru dibuat. Perbarui LICENSE_PUBKEY di extension & rilis ulang.');
    }
}
