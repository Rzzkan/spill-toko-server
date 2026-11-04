<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\App as AppModel;
use App\Models\Subscription;
use App\Services\AppCatalog;
use Illuminate\Http\Request;

/** Kelola aplikasi TemanSeller (Shofee dan aplikasi lain): nama, trial, paket & harga. */
class AppController extends Controller
{
    public function index()
    {
        $apps = AppCatalog::all();
        $subs = Subscription::query()->selectRaw('app, count(*) as n')->groupBy('app')->pluck('n', 'app');
        return view('admin.apps.index', ['apps' => $apps, 'subs' => $subs]);
    }

    public function create()
    {
        return view('admin.apps.form', ['app' => new AppModel(['active' => true, 'trial_days' => 3, 'sort' => (int) AppModel::query()->max('sort') + 1]), 'isNew' => true]);
    }

    private function rules(bool $new): array
    {
        // harga 0 = paket tidak dijual; bila dijual minimal Rp1.000 (batas Midtrans)
        $price = ['nullable', 'integer', 'min:0', fn ($a, $v, $fail) => ((int) $v > 0 && (int) $v < 1000) ? $fail('Harga minimal Rp1.000 (atau 0 untuk tidak dijual).') : null];
        return [
            'slug' => $new ? ['required', 'regex:/^[a-z][a-z0-9\-]{1,29}$/', 'unique:apps,slug'] : ['prohibited'],
            'name' => ['required', 'string', 'max:80'], 'tagline' => ['nullable', 'string', 'max:200'],
            'url' => ['nullable', 'string', 'max:255', 'regex:#^(https://[^\s]+|/[A-Za-z0-9._~\-/?=&%\#]*)$#'], 'download_url' => ['nullable', 'string', 'max:255', 'regex:#^https://[^\s]+$#'], 'icon' => ['nullable', 'string', 'max:16'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:60'], 'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'monthly_price' => $price, 'monthly_original' => ['nullable', 'integer', 'min:0'], 'monthly_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'yearly_price' => $price, 'yearly_original' => ['nullable', 'integer', 'min:0'], 'yearly_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'lifetime_price' => $price,
        ];
    }

    private function fill(Request $r, AppModel $app, bool $new): AppModel
    {
        $d = $r->validate($this->rules($new));
        $plans = [
            'monthly' => ['price' => (int) ($d['monthly_price'] ?? 0), 'original' => (int) ($d['monthly_original'] ?? 0), 'days' => (int) ($d['monthly_days'] ?? 30) ?: 30],
            'yearly' => ['price' => (int) ($d['yearly_price'] ?? 0), 'original' => (int) ($d['yearly_original'] ?? 0), 'days' => (int) ($d['yearly_days'] ?? 365) ?: 365],
            'lifetime' => ['enabled' => $r->boolean('lifetime_enabled'), 'price' => (int) ($d['lifetime_price'] ?? 0)],
        ];
        $app->fill(['name' => $d['name'], 'tagline' => $d['tagline'] ?? null, 'url' => ($d['url'] ?? '') !== '' ? $d['url'] : null, 'download_url' => ($d['download_url'] ?? '') !== '' ? $d['download_url'] : null, 'icon' => $d['icon'] ?? null, 'trial_days' => (int) $d['trial_days'],
            'sort' => (int) ($d['sort'] ?? 0), 'active' => $r->boolean('active'), 'plans' => $plans]);
        return $app;
    }

    public function store(Request $r)
    {
        $app = new AppModel(['slug' => (string) $r->input('slug')]);
        $this->fill($r, $app, true)->save();
        AdminLog::record('app_create', 'Aplikasi baru: ' . $app->name);
        return redirect()->route('admin.apps')->with('ok', 'Aplikasi ' . $app->name . ' ditambahkan.');
    }

    public function edit(AppModel $app)
    {
        return view('admin.apps.form', ['app' => $app, 'isNew' => false]);
    }

    public function update(Request $r, AppModel $app)
    {
        if ($app->slug === AppModel::DEFAULT && !$r->boolean('active')) return back()->with('error', 'Aplikasi utama (Shofee) tidak bisa dinonaktifkan.');
        $this->fill($r, $app, false)->save();
        AdminLog::record('app_update', 'Aplikasi diubah: ' . $app->name);
        return redirect()->route('admin.apps')->with('ok', 'Aplikasi ' . $app->name . ' disimpan.');
    }
}
