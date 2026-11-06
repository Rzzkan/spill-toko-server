<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\App as AppModel;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Ai;
use App\Services\AppCatalog;
use App\Services\GoogleAuth;
use App\Services\Midtrans;
use App\Services\Plans;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Portal pengguna di web: masuk dengan Google, lihat status langganan, bayar / perpanjang. */
class PortalController extends Controller
{
    public function loginForm(Request $r)
    {
        if ($r->session()->has('customer_id')) return redirect()->route('portal.account');
        $nonce = Str::random(32);
        $r->session()->put('portal_nonce', $nonce);
        return view('portal.login', ['clientId' => (string) Settings::get('google.client_id', ''), 'nonce' => $nonce]);
    }

    public function loginGoogle(Request $r)
    {
        $nonce = (string) $r->session()->pull('portal_nonce', '');
        try {
            $g = GoogleAuth::verify((string) $r->input('credential', ''), $nonce);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
        $c = Customer::fromGoogle($g);
        $r->session()->regenerate();
        $r->session()->put('customer_id', $c->id);
        $next = $r->session()->pull('login_next');
        if ($next === '/audit') return response()->json(['redirect' => route('audit')]);
        return response()->json(['redirect' => $r->session()->has('buy_plan') ? route('portal.buy.go') : ($r->session()->has('layanan_plan') ? route('portal.layanan') : route('portal.account'))]);
    }

    public function account(Request $r)
    {
        $c = $r->attributes->get('customer');
        $this->syncPending($c->id);
        $apps = AppModel::listActive()->map(function (AppModel $app) use ($c) {
            $sub = $c->subscription($app->slug);   // null = belum pernah memakai aplikasi ini
            $a = $sub?->access() ?? \App\Models\Subscription::none();
            return ['app' => $app, 'a' => $a, 'sub' => $sub];
        });
        return view('portal.account', [
            'c' => $c, 'layananSubs' => $c->serviceSubscriptions()->limit(5)->get(), 'apps' => $apps, 'plansByApp' => $apps->mapWithKeys(fn ($x) => [$x['app']->slug => Plans::all($x['app']->slug)])->all(),
            'orders' => Order::query()->where('customer_id', $c->id)->latest('id')->limit(15)->get(),
            'appNames' => AppCatalog::names(),
            'devices' => \Illuminate\Support\Facades\Schema::hasTable('customer_devices') ? $c->devices()->orderByDesc('last_seen_at')->get() : collect(), 'maxDevices' => max(1, (int) config('temanseller.max_devices', 2)),
        ]);
    }

    /** Order pending (maks. 3 hari terakhir) dicek ke Midtrans supaya status akurat walau notifikasi belum masuk. */
    private function syncPending(int $customerId): void
    {
        if (!Midtrans::configured()) return;
        Order::query()->where('customer_id', $customerId)->where('status', 'pending')->where('created_at', '>', now()->subDays(3))->latest('id')->limit(3)->get()
            ->each(function (Order $o) { try { Midtrans::syncOrder($o); } catch (\Throwable) { /* abaikan; tampilan tetap pending */ } });
    }

    /** Rincian langganan satu aplikasi + pilihan paket. */
    public function app(Request $r, string $slug)
    {
        $app = AppModel::slugOk($slug) ? AppModel::bySlug($slug) : null;
        if (!$app || !$app->active) abort(404);
        $c = $r->attributes->get('customer');
        $this->syncPending($c->id);
        $sub = $c->subscription($slug);
        $a = $sub?->access() ?? \App\Models\Subscription::none();
        return view('portal.app', [
            'c' => $c, 'appRow' => $app, 'a' => $a, 'sub' => $sub, 'plans' => Plans::all($slug), 'trialDays' => Plans::trialDays($slug),
            'ai' => Ai::info($c), 'canPay' => Midtrans::configured(),
            'orders' => Order::query()->where('customer_id', $c->id)->where('app', $slug)->latest('id')->limit(10)->get(),
        ]);
    }

    /** Buat order + transaksi Midtrans lalu arahkan ke halaman bayar. Galat -> kembali dengan pesan. */
    private function checkout(Customer $c, string $plan, string $errorTo, string $slug = AppModel::DEFAULT)
    {
        $app = AppModel::slugOk($slug) ? AppModel::bySlug($slug) : null;
        if (!$app || !$app->active) return redirect($errorTo)->with('error', 'Aplikasi tidak dikenal.');
        $p = Plans::find($plan, $slug);
        if (!$p) return redirect($errorTo)->with('error', 'Paket tidak dikenal.');
        $sub = $c->subscription($slug, true);
        if ($sub->lifetime) return redirect($errorTo)->with('error', 'Akunmu sudah Lifetime untuk ' . $app->name . ', tidak perlu berlangganan.');
        $order = Order::query()->create(['order_id' => 'ST-' . time() . '-' . Str::lower(Str::random(8)), 'customer_id' => $c->id, 'app' => $slug,
            'plan' => $plan, 'amount' => $p['price'], 'status' => 'pending']);
        try {
            $t = Midtrans::createTransaction($order, $c->email, (string) $c->name, $p['label'], $app->name);
        } catch (ApiException $e) {
            $order->update(['status' => 'failed']);
            return redirect($errorTo)->with('error', $e->getMessage());
        }
        $order->update(['snap_token' => $t['token']]);
        return redirect()->away($t['redirect_url']);
    }

    public function pay(Request $r, string $app = AppModel::DEFAULT)
    {
        return $this->checkout($r->attributes->get('customer'), (string) $r->input('plan'), route('portal.app', $app), $app);
    }

    /** Tombol harga di landing page: /beli/{plan} (Shofee) atau /beli/{app}/{plan} -> (login Google bila belum) -> langsung ke Midtrans. */
    public function buy(Request $r, string $plan, string $app = AppModel::DEFAULT)
    {
        if (!AppModel::slugOk($app) || !Plans::find($plan, $app)) return redirect('/#harga');
        $r->session()->put('buy_plan', $plan);
        $r->session()->put('buy_app', $app);
        return $r->session()->has('customer_id') ? redirect()->route('portal.buy.go') : redirect()->route('portal.login');
    }

    public function buyGo(Request $r)
    {
        $plan = (string) $r->session()->pull('buy_plan', '');
        $app = (string) $r->session()->pull('buy_app', AppModel::DEFAULT);
        if ($plan === '') return redirect()->route('portal.account');
        return $this->checkout($r->attributes->get('customer'), $plan, route('portal.app', $app), $app);
    }

    /** Lepas satu browser dari akun: token extension di browser itu langsung tidak berlaku. */
    public function revokeDevice(Request $r, int $id)
    {
        $c = $r->attributes->get('customer');
        $n = $c->devices()->whereKey($id)->delete();
        return redirect()->route('portal.account')->with($n ? 'ok' : 'error', $n ? 'Browser dilepas. Slot-nya kini bisa dipakai browser lain.' : 'Browser tidak ditemukan.');
    }

    public function logout(Request $r)
    {
        $r->session()->forget('customer_id');
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect('/');
    }
}
