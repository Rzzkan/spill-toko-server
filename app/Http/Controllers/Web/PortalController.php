<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Ai;
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
        return response()->json(['redirect' => $r->session()->has('buy_plan') ? route('portal.buy.go') : route('portal.account')]);
    }

    public function account(Request $r)
    {
        $c = $r->attributes->get('customer');
        return view('portal.account', [
            'c' => $c, 'a' => $c->access(), 'plans' => Plans::all(), 'trialDays' => Plans::trialDays(),
            'orders' => Order::query()->where('customer_id', $c->id)->latest('id')->limit(15)->get(),
            'ai' => Ai::info($c), 'canPay' => Midtrans::configured(),
            'devices' => \Illuminate\Support\Facades\Schema::hasTable('customer_devices') ? $c->devices()->orderByDesc('last_seen_at')->get() : collect(), 'maxDevices' => max(1, (int) config('spill.max_devices', 2)),
        ]);
    }

    /** Buat order + transaksi Midtrans lalu arahkan ke halaman bayar. Galat -> kembali dengan pesan. */
    private function checkout(Customer $c, string $plan, string $errorTo)
    {
        $p = Plans::find($plan);
        if (!$p) return redirect($errorTo)->with('error', 'Paket tidak dikenal.');
        if ($plan === 'lifetime' && $c->lifetime) return redirect($errorTo)->with('error', 'Akun ini sudah Lifetime.');
        if ($c->lifetime) return redirect($errorTo)->with('error', 'Akun Anda sudah Lifetime, tidak perlu berlangganan.');
        $order = Order::query()->create(['order_id' => 'ST-' . time() . '-' . Str::lower(Str::random(8)), 'customer_id' => $c->id,
            'plan' => $plan, 'amount' => $p['price'], 'status' => 'pending']);
        try {
            $t = Midtrans::createTransaction($order, $c->email, (string) $c->name, $p['label']);
        } catch (ApiException $e) {
            $order->update(['status' => 'failed']);
            return redirect($errorTo)->with('error', $e->getMessage());
        }
        $order->update(['snap_token' => $t['token']]);
        return redirect()->away($t['redirect_url']);
    }

    public function pay(Request $r)
    {
        return $this->checkout($r->attributes->get('customer'), (string) $r->input('plan'), route('portal.account'));
    }

    /** Tombol harga di landing page: /beli/{plan} -> (login Google bila belum) -> langsung ke Midtrans. */
    public function buy(Request $r, string $plan)
    {
        if (!Plans::find($plan)) return redirect('/#harga');
        $r->session()->put('buy_plan', $plan);
        return $r->session()->has('customer_id') ? redirect()->route('portal.buy.go') : redirect()->route('portal.login');
    }

    public function buyGo(Request $r)
    {
        $plan = (string) $r->session()->pull('buy_plan', '');
        if ($plan === '') return redirect()->route('portal.account');
        return $this->checkout($r->attributes->get('customer'), $plan, route('portal.account'));
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
