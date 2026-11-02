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
        return response()->json(['redirect' => route('portal.account')]);
    }

    public function account(Request $r)
    {
        $c = $r->attributes->get('customer');
        return view('portal.account', [
            'c' => $c, 'a' => $c->access(), 'plans' => Plans::all(), 'trialDays' => Plans::trialDays(),
            'orders' => Order::query()->where('customer_id', $c->id)->latest('id')->limit(15)->get(),
            'ai' => Ai::info($c), 'canPay' => Midtrans::configured(),
        ]);
    }

    public function pay(Request $r)
    {
        $c = $r->attributes->get('customer');
        $plan = (string) $r->input('plan');
        $p = Plans::find($plan);
        if (!$p) return back()->with('error', 'Paket tidak dikenal.');
        if ($plan === 'lifetime' && $c->lifetime) return back()->with('error', 'Akun ini sudah Lifetime.');
        $order = Order::query()->create(['order_id' => 'ST-' . time() . '-' . Str::lower(Str::random(8)), 'customer_id' => $c->id,
            'plan' => $plan, 'amount' => $p['price'], 'status' => 'pending']);
        try {
            $t = Midtrans::createTransaction($order, $c->email, (string) $c->name, $p['label']);
        } catch (ApiException $e) {
            $order->update(['status' => 'failed']);
            return back()->with('error', $e->getMessage());
        }
        $order->update(['snap_token' => $t['token']]);
        return redirect()->away($t['redirect_url']);
    }

    public function logout(Request $r)
    {
        $r->session()->forget('customer_id');
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect('/');
    }
}
