<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\App as AppModel;
use App\Services\Ai;
use App\Services\AppCatalog;
use App\Services\Plans;
use App\Services\Settings;
use Illuminate\Http\Request;

/** Halaman publik: hub TemanSeller (temanseller.com) dan landing Shofee (shofee.temanseller.com atau /shofee). */
class PublicController extends Controller
{
    /** URL landing Shofee: subdomain bila diatur lewat SHOFEE_URL, kalau belum ada pakai /shofee. */
    public static function shofeeUrl(): string
    {
        return rtrim((string) config('temanseller.shofee_url'), '/') ?: url('/shofee');
    }

    public function home(Request $r)
    {
        // subdomain shofee.* hanya jalan pintas: alihkan ke temanseller.com/shofee (tanpa masking)
        if (str_starts_with(strtolower($r->getHost()), 'shofee.')) {
            return redirect()->away(rtrim((string) config('app.url'), '/') . '/shofee', 301);
        }
        return $this->hub();
    }

    public function hub()
    {
        $apps = AppCatalog::active()->map(fn (AppModel $a) => AppCatalog::summary($a) + [
            'url' => $a->landingUrl()]);
        $on = \App\Services\Layanan::enabled();
        $types = $on ? \App\Models\ServiceType::query()->where('is_active', true)->orderBy('sort')->orderBy('id')
            ->with(['plans' => fn ($q) => $q->where('is_active', true)->where('price', '>', 0)])->get()->filter(fn ($t) => $t->plans->isNotEmpty())->values() : collect();
        return view('public.hub', ['apps' => $apps, 'layananOn' => $on, 'layananTypes' => $types,
            'layananFrom' => $on ? \App\Models\ServicePlan::orderable()->reorder()->min('price') : null]);
    }

    public function shofee()
    {
        return view('public.home', [
            'trial' => Plans::trialDays(), 'plans' => Plans::all(), 'aiq' => Ai::quotas(), 'aiOn' => Ai::on(), 'store' => (string) AppCatalog::find(AppModel::DEFAULT)?->download_url,
            'hubUrl' => str_starts_with(strtolower(request()->getHost()), 'shofee.') ? preg_replace('#^(https?://)shofee\.#i', '$1', url('/')) : url('/'),
        ]);
    }

    public function privacy()
    {
        return view('public.privasi', ['email' => (string) config('temanseller.contact_email')]);
    }

    /** Halaman setelah pembayaran: tampilan mengikuti status order yang sebenarnya (dicek ke Midtrans), bukan selalu "berhasil". */
    public function thanks(Request $r)
    {
        $id = preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($r->query('order') ?: $r->query('order_id')));
        $order = $id !== '' ? \App\Models\Order::query()->where('order_id', $id)->first() : null;
        $status = $order?->status;
        if ($order && $order->status === 'pending') {
            try { $status = \App\Services\Midtrans::syncOrder($order) ?? 'pending'; } catch (\Throwable) { $status = 'pending'; }
        }
        return view('public.thanks', ['order' => $id, 'status' => $order ? $status : 'unknown']);
    }
}
