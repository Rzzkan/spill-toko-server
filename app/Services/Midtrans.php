<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Midtrans
{
    public static function configured(): bool { return Settings::has('midtrans.server_key'); }

    public static function snapUrl(): string
    {
        return config('spill.midtrans_snap_url') ?: (Settings::bool('midtrans.production')
            ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions');
    }

    public static function apiUrl(): string
    {
        return rtrim(config('spill.midtrans_api_url') ?: (Settings::bool('midtrans.production')
            ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com'), '/');
    }

    private static function http(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(20)->withBasicAuth((string) Settings::get('midtrans.server_key', ''), '');
    }

    /** @return array{redirect_url:string, token:?string} */
    public static function createTransaction(Order $order, string $email, string $name, string $label): array
    {
        if (!self::configured()) throw new ApiException('Pembayaran belum dikonfigurasi admin', 503);
        try {
            $r = self::http()->post(self::snapUrl(), [
                'transaction_details' => ['order_id' => $order->order_id, 'gross_amount' => $order->amount],
                'item_details' => [['id' => $order->plan, 'price' => $order->amount, 'quantity' => 1, 'name' => 'Shofee - ' . $label]],
                'customer_details' => ['email' => $email, 'first_name' => $name ?: 'Pelanggan'],
                'callbacks' => ['finish' => url('/thanks') . '?order=' . $order->order_id],
                'expiry' => ['unit' => 'hours', 'duration' => 24],
            ]);
        } catch (\Throwable) {
            $r = null;
        }
        $j = $r?->json();
        if (!$r || !in_array($r->status(), [200, 201], true) || empty($j['redirect_url'])) {
            logger()->error('Midtrans snap error', ['status' => $r?->status(), 'body' => $j]);
            throw new ApiException('Gagal membuat pembayaran, coba lagi', 502);
        }
        return ['redirect_url' => $j['redirect_url'], 'token' => $j['token'] ?? null];
    }

    public static function signatureValid(array $n): bool
    {
        $expect = hash('sha512', ($n['order_id'] ?? '') . ($n['status_code'] ?? '') . ($n['gross_amount'] ?? '') . (string) Settings::get('midtrans.server_key', ''));
        return hash_equals($expect, (string) ($n['signature_key'] ?? ''));
    }

    /** Status transaksi langsung dari Midtrans (jangan percaya payload webhook mentah). */
    public static function status(string $orderId): ?array
    {
        try {
            $r = self::http()->get(self::apiUrl() . '/v2/' . rawurlencode($orderId) . '/status');
        } catch (\Throwable) { return null; }
        return $r->status() === 200 && is_array($r->json()) ? $r->json() : null;
    }

    /** Tes Server Key: order palsu -> 404 "tidak ditemukan" berarti key valid; 401 berarti key salah / beda mode. */
    public static function test(): array
    {
        if (!self::configured()) return [false, 'Server Key belum diisi.'];
        try {
            $r = self::http()->get(self::apiUrl() . '/v2/spilltoko-test-' . time() . '/status');
        } catch (\Throwable $e) { return [false, 'Tidak bisa menghubungi Midtrans.']; }
        $code = (int) ($r->json('status_code') ?? $r->status());
        if ($code === 401 || $r->status() === 401) return [false, 'Server Key ditolak (salah, atau tidak sesuai mode ' . (Settings::bool('midtrans.production') ? 'Production' : 'Sandbox') . ').'];
        if ($code === 404 || $r->status() === 404) return [true, 'Terhubung ✓ Server Key valid (mode ' . (Settings::bool('midtrans.production') ? 'Production' : 'Sandbox') . ').'];
        return [false, 'Respons tak terduga dari Midtrans (HTTP ' . $r->status() . ').'];
    }

    /** Aktifkan paket setelah dibayar. Idempoten: notifikasi ganda tidak menambah masa aktif dua kali. */
    public static function markPaid(string $orderId, ?string $paymentType, string $raw): void
    {
        DB::transaction(function () use ($orderId, $paymentType, $raw) {
            $o = Order::query()->where('order_id', $orderId)->lockForUpdate()->first();
            if (!$o || $o->status === 'paid') return;
            $o->update(['status' => 'paid', 'paid_at' => now(), 'payment_type' => $paymentType, 'raw_notification' => $raw]);
            $c = $o->customer()->lockForUpdate()->first();
            if ($o->plan === 'lifetime') {
                $c->update(['lifetime' => true]);
            } else {
                $days = (int) (Plans::find($o->plan)['days'] ?? ['monthly' => 30, 'yearly' => 365][$o->plan] ?? 30);
                $base = max(time(), $c->paid_until?->getTimestamp() ?? 0);
                $c->update(['paid_until' => \Illuminate\Support\Carbon::createFromTimestampUTC($base + $days * 86400), 'paid_plan' => $o->plan]);
            }
        });
    }
}
