<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\App as AppModel;
use App\Services\Ai;
use App\Services\Plans;
use App\Services\Settings;
use Illuminate\Http\Request;

/** Halaman publik: hub TemanSeller (temanseller.com) dan landing Shofee (shofee.temanseller.com atau /shofee). */
class PublicController extends Controller
{
    /** URL landing Shofee: subdomain bila diatur lewat SHOFEE_URL, kalau belum ada pakai /shofee. */
    public static function shofeeUrl(): string
    {
        return rtrim((string) config('spill.shofee_url'), '/') ?: url('/shofee');
    }

    public function home(Request $r)
    {
        return str_starts_with(strtolower($r->getHost()), 'shofee.') ? $this->shofee() : $this->hub();
    }

    public function hub()
    {
        $apps = AppModel::listActive()->map(function (AppModel $a) {
            $per = array_map(fn ($p) => (int) $p['price'] / max(1, $p['days'] / 30), array_filter(Plans::all($a->slug), fn ($p) => !empty($p['days']) && (int) $p['price'] > 0));
            $from = $per ? min($per) : null;
            return ['app' => $a, 'trial' => Plans::trialDays($a->slug), 'from' => $from ? (int) round($from) : null,
                'url' => $a->slug === AppModel::DEFAULT ? self::shofeeUrl() : url('/akun/aplikasi/' . $a->slug)];
        });
        return view('public.hub', ['apps' => $apps]);
    }

    public function shofee()
    {
        return view('public.home', [
            'trial' => Plans::trialDays(), 'plans' => Plans::all(), 'aiq' => Ai::quotas(), 'aiOn' => Ai::on(), 'store' => Settings::get('app.chrome_store_url', ''),
            'hubUrl' => str_starts_with(strtolower(request()->getHost()), 'shofee.') ? preg_replace('#^(https?://)shofee\.#i', '$1', url('/')) : url('/'),
        ]);
    }
}
