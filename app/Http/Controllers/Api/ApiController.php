<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\Order;
use App\Services\Ai;
use App\Services\GoogleAuth;
use App\Services\Jwt;
use App\Services\License;
use App\Services\Midtrans;
use App\Services\Plans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Kontrak API untuk extension (tidak boleh berubah): plans, auth, me, checkout, orders, ai/analyze, midtrans/notify */
class ApiController extends Controller
{
    private function out(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function cust(Request $r): Customer { return $r->attributes->get('customer'); }

    private function plansPublic(): array
    {
        return collect(Plans::all())->map(fn ($p, $k) => ['label' => $p['label'], 'price' => $p['price'], 'original' => $p['original'] ?? 0, 'days' => $p['days'], 'ai_limit' => Ai::quota($k)])->all();
    }

    /** Dukungan gaya lama: /api/index.php?r=plans */
    public function gateway(Request $r)
    {
        $route = trim((string) $r->query('r', ''), '/');
        $map = [
            'GET plans' => 'plans', 'POST auth' => 'auth', 'GET me' => 'me', 'POST checkout' => 'checkout',
            'GET orders' => 'orders', 'POST ai/analyze' => 'analyze', 'POST midtrans/notify' => 'notify',
        ];
        $m = $map[$r->method() . ' ' . $route] ?? null;
        if (!$m) throw new ApiException('Endpoint tidak ditemukan', 404);
        if (in_array($m, ['me', 'checkout', 'orders', 'analyze'], true)) {
            return app(\App\Http\Middleware\CustomerAuth::class)->handle($r, fn ($req) => $this->$m($req));
        }
        return $this->$m($r);
    }

    public function plans(): JsonResponse
    {
        return $this->out(['plans' => $this->plansPublic(), 'trial_days' => Plans::trialDays()]);
    }

    public function auth(Request $r): JsonResponse
    {
        $g = GoogleAuth::verify((string) $r->input('id_token', ''), (string) $r->input('nonce', ''));
        $did = trim((string) $r->input('device_id', ''));
        if (!preg_match('/^[A-Za-z0-9\-]{16,64}$/', $did)) throw new ApiException('Versi extension terlalu lama. Perbarui extension lalu masuk lagi.', 400);
        $c = Customer::fromGoogle($g);
        $hash = CustomerDevice::hash($did);
        $dev = $c->devices()->where('device_hash', $hash)->first();
        if (!$dev) {
            $max = max(1, (int) config('spill.max_devices', 2));
            if ($c->devices()->count() >= $max) {
                throw new ApiException("Akun ini sudah dipakai di $max browser (batas maksimal). Masuk di " . url('/login') . ' lalu lepas salah satu browser di halaman Akun, setelah itu coba masuk lagi.', 403);
            }
            $dev = new CustomerDevice(['customer_id' => $c->id, 'device_hash' => $hash]);
        }
        $dev->fill(['label' => CustomerDevice::labelFrom((string) $r->input('device_name', ''), (string) $r->userAgent()), 'ip' => $r->ip(), 'last_seen_at' => now()])->save();
        $token = Jwt::sign(['sub' => $c->id, 'dev' => $dev->id, 'iat' => time(), 'exp' => time() + 60 * 86400]);
        return $this->out(['token' => $token, 'license' => License::make($c), 'plans' => $this->plansPublic(), 'ai' => Ai::info($c)]);
    }

    public function me(Request $r): JsonResponse
    {
        $c = $this->cust($r);
        return $this->out(['license' => License::make($c), 'plans' => $this->plansPublic(), 'ai' => Ai::info($c)]);
    }

    public function checkout(Request $r): JsonResponse
    {
        $c = $this->cust($r);
        $plan = (string) $r->input('plan', '');
        $p = Plans::find($plan);
        if (!$p) throw new ApiException('Paket tidak dikenal', 400);
        if ($plan === 'lifetime' && $c->lifetime) throw new ApiException('Akun ini sudah Lifetime', 409);
        $order = Order::query()->create(['order_id' => 'ST-' . time() . '-' . Str::lower(Str::random(8)), 'customer_id' => $c->id,
            'plan' => $plan, 'amount' => $p['price'], 'status' => 'pending']);
        try {
            $t = Midtrans::createTransaction($order, $c->email, (string) $c->name, $p['label']);
        } catch (ApiException $e) {
            $order->update(['status' => 'failed']);
            throw $e;
        }
        $order->update(['snap_token' => $t['token']]);
        return $this->out(['order_id' => $order->order_id, 'redirect_url' => $t['redirect_url']]);
    }

    public function orders(Request $r): JsonResponse
    {
        $rows = Order::query()->where('customer_id', $this->cust($r)->id)->latest('id')->limit(20)->get()
            ->map(fn ($o) => ['order_id' => $o->order_id, 'plan' => $o->plan, 'amount' => $o->amount, 'status' => $o->status,
                'created_at' => $o->created_at?->utc()->format('Y-m-d H:i:s'), 'paid_at' => $o->paid_at?->utc()->format('Y-m-d H:i:s')]);
        return $this->out(['orders' => $rows]);
    }

    public function analyze(Request $r): JsonResponse
    {
        $res = Ai::analyze($this->cust($r), $r->json()->all() ?: $r->all());
        return $this->out($res);
    }

    public function notify(Request $r): JsonResponse
    {
        $n = $r->json()->all() ?: $r->all();
        $orderId = (string) ($n['order_id'] ?? '');
        if (!Midtrans::signatureValid($n)) throw new ApiException('invalid signature', 403);
        $order = Order::query()->where('order_id', $orderId)->first();
        if (!$order) return $this->out(['ok' => true, 'note' => 'unknown order']);

        $s = Midtrans::status($orderId);   // verifikasi ulang ke Midtrans
        if (!$s || ($s['order_id'] ?? '') !== $orderId || (string) ($s['status_code'] ?? '') === '404') throw new ApiException('status check failed', 502);
        if ((int) round((float) ($s['gross_amount'] ?? 0)) !== (int) $order->amount) {
            logger()->error("amount mismatch $orderId");
            throw new ApiException('amount mismatch', 400);
        }
        $ts = (string) ($s['transaction_status'] ?? '');
        $fraud = (string) ($s['fraud_status'] ?? 'accept');
        if ($ts === 'settlement' || ($ts === 'capture' && $fraud === 'accept')) {
            Midtrans::markPaid($orderId, $s['payment_type'] ?? null, json_encode($s));
        } elseif (in_array($ts, ['expire', 'cancel', 'deny'], true)) {
            Order::query()->where('order_id', $orderId)->where('status', '<>', 'paid')
                ->update(['status' => ['expire' => 'expired', 'cancel' => 'cancelled', 'deny' => 'denied'][$ts]]);
        }
        return $this->out(['ok' => true]);
    }
}
