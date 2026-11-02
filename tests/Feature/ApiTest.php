<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Services\Jwt;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $sub = 'g1', string $email = 'a@b.test'): array
    {
        Settings::set('google.client_id', 'cid.apps.googleusercontent.com');
        Http::fake(['*tokeninfo*' => Http::response(['aud' => 'cid.apps.googleusercontent.com', 'iss' => 'accounts.google.com', 'exp' => time() + 600,
            'sub' => $sub, 'email' => $email, 'email_verified' => 'true', 'name' => 'Budi', 'nonce' => 'n1'])]);
        $r = $this->postJson('/api/index.php?r=auth', ['id_token' => 'tok', 'nonce' => 'n1']);
        $r->assertOk();
        return [$r->json('token'), $r];
    }

    public function test_plans_and_unknown_route(): void
    {
        $this->getJson('/api/index.php?r=plans')->assertOk()->assertJsonPath('plans.monthly.price', 24000)->assertJsonPath('trial_days', 3);
        $this->getJson('/api/index.php?r=nope')->assertStatus(404)->assertJsonPath('error', 'Endpoint tidak ditemukan');
        $this->getJson('/api/index.php?r=me')->assertStatus(401);
    }

    public function test_auth_requires_google_config_and_matching_nonce(): void
    {
        $this->postJson('/api/index.php?r=auth', ['id_token' => 'x', 'nonce' => 'n'])->assertStatus(503);
        Settings::set('google.client_id', 'cid.apps.googleusercontent.com');
        Http::fake(['*tokeninfo*' => Http::response(['aud' => 'lain', 'iss' => 'accounts.google.com', 'exp' => time() + 600, 'sub' => '1', 'email' => 'a@b.c', 'email_verified' => 'true', 'nonce' => 'n'])]);
        $this->postJson('/api/index.php?r=auth', ['id_token' => 'x', 'nonce' => 'n'])->assertStatus(401);
    }

    public function test_auth_creates_trial_user_and_signed_license(): void
    {
        [$token, $r] = $this->login();
        $r->assertJsonPath('license.payload', fn ($p) => json_decode($p, true)['plan'] === 'trial' && json_decode($p, true)['access'] === true);
        $payload = $r->json('license.payload');
        $pub = base64_decode(\App\Services\License::publicKey());
        $this->assertTrue(sodium_crypto_sign_verify_detached(base64_decode($r->json('license.sig')), $payload, $pub));
        $this->assertSame(1, Customer::count());
        $this->getJson('/api/index.php?r=me', ['Authorization' => "Bearer $token"])->assertOk()->assertJsonStructure(['license', 'plans', 'ai' => ['enabled', 'auto', 'used', 'limit']]);
    }

    public function test_checkout_then_webhook_is_idempotent_and_verifies(): void
    {
        [$token] = $this->login();
        Settings::set('midtrans.server_key', 'SB-Mid-server-xyz');
        Http::fake(['*snap/v1/transactions' => Http::response(['token' => 'T', 'redirect_url' => 'https://pay.test/x'], 201)]);
        $r = $this->postJson('/api/index.php?r=checkout', ['plan' => 'monthly'], ['Authorization' => "Bearer $token"])->assertOk();
        $oid = $r->json('order_id');
        $this->assertSame('https://pay.test/x', $r->json('redirect_url'));

        $note = ['order_id' => $oid, 'status_code' => '200', 'gross_amount' => '24000.00'];
        $note['signature_key'] = hash('sha512', $oid . '200' . '24000.00' . 'SB-Mid-server-xyz');
        $this->postJson('/api/index.php?r=midtrans/notify', array_merge($note, ['signature_key' => 'salah']))->assertStatus(403);

        Http::fake(['*/v2/*/status' => fn ($req) => Http::response(str_contains($req->url(), 'ST-2')
            ? ['order_id' => 'ST-2', 'status_code' => '200', 'gross_amount' => '1.00', 'transaction_status' => 'settlement']
            : ['order_id' => $oid, 'status_code' => '200', 'gross_amount' => '24000.00', 'transaction_status' => 'settlement', 'payment_type' => 'qris'])]);
        $this->postJson('/api/index.php?r=midtrans/notify', $note)->assertOk();
        $until = Customer::first()->paid_until;
        $this->assertNotNull($until);
        $this->postJson('/api/index.php?r=midtrans/notify', $note)->assertOk();   // kirim ulang -> tidak menambah hari lagi
        $this->assertEquals($until->getTimestamp(), Customer::first()->fresh()->paid_until->getTimestamp());
        $this->assertSame('paid', Order::first()->status);

        // jumlah di Midtrans tidak cocok -> ditolak
        $o2 = Order::query()->create(['order_id' => 'ST-2', 'customer_id' => Customer::first()->id, 'plan' => 'lifetime', 'amount' => 200000, 'status' => 'pending']);
        $n2 = ['order_id' => 'ST-2', 'status_code' => '200', 'gross_amount' => '1.00'];
        $n2['signature_key'] = hash('sha512', 'ST-2200' . '1.00' . 'SB-Mid-server-xyz');
        $this->postJson('/api/index.php?r=midtrans/notify', $n2)->assertStatus(400);
        $this->assertFalse((bool) Customer::first()->fresh()->lifetime);
    }

    public function test_ai_analyze_cache_quota_and_fallback(): void
    {
        [$token] = $this->login();
        $h = ['Authorization' => "Bearer $token"];
        $body = ['kind' => 'product', 'data' => ['judul' => 'Kebaya', 'harga' => 100000, 'terjual' => 500]];
        $this->postJson('/api/index.php?r=ai/analyze', $body, $h)->assertStatus(503);   // belum aktif/key kosong

        Settings::set('ai.api_key', 'kn-test');
        Settings::set('ai.quota.trial', '2');
        Settings::set('ai.rate_per_min', '0');
        Settings::set('ai.fallback_model', 'model-b');
        $good = ['ringkasan' => 'Bagus', 'skor' => 82, 'label' => 'Sangat Potensial', 'temuan' => ['a'], 'peluang' => ['b'], 'risiko' => ['c'],
            'rekomendasi' => [['aksi' => 'Naikkan foto', 'alasan' => 'x', 'prioritas' => 'tinggi']], 'kata_kunci' => ['kebaya'], 'saran_judul' => ['Kebaya Modern']];
        Http::fake(['*/chat/completions' => Http::sequence()
            ->push(['error' => ['message' => 'boom']], 500)                                  // model utama gagal
            ->push(['model' => 'model-b', 'choices' => [['message' => ['content' => "```json\n" . json_encode($good) . "\n```"]]], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20]])
            ->push(['model' => 'm', 'choices' => [['message' => ['content' => json_encode($good)]]]])]);   // analisis ke-3 (toko T1)
        $r = $this->postJson('/api/index.php?r=ai/analyze', $body, $h);
        $r->assertOk();
        $r->assertJsonPath('analysis.skor', 82)->assertJsonPath('cached', false)->assertJsonPath('ai.used', 1)->assertJsonPath('analysis.model', 'model-b');

        $this->postJson('/api/index.php?r=ai/analyze', $body, $h)->assertOk()->assertJsonPath('cached', true)->assertJsonPath('ai.used', 1);   // cache tak memakai kuota

        // kuota habis (limit 2): satu analisis baru lagi lolos, berikutnya 429
        $this->postJson('/api/index.php?r=ai/analyze', ['kind' => 'shop', 'data' => ['nama' => 'T1']], $h)->assertOk();
        $this->postJson('/api/index.php?r=ai/analyze', ['kind' => 'shop', 'data' => ['nama' => 'T2']], $h)->assertStatus(429);
        $this->postJson('/api/index.php?r=ai/analyze', ['kind' => 'zzz', 'data' => ['a' => 1]], $h)->assertStatus(400);
    }

    public function test_ai_provider_errors_are_generic_for_users(): void
    {
        [$token] = $this->login();
        Settings::set('ai.api_key', 'kn-test');
        Http::fake(['*/chat/completions' => Http::response(['error' => ['message' => 'insufficient balance']], 402)]);
        $this->postJson('/api/index.php?r=ai/analyze', ['kind' => 'product', 'data' => ['a' => 1]], ['Authorization' => "Bearer $token"])
            ->assertStatus(502)->assertJsonPath('error', 'Layanan AI sedang bermasalah, hubungi admin.');
        $this->assertDatabaseHas('ai_logs', ['status' => 'error', 'error' => 'Saldo kenari.id tidak cukup']);
    }

    public function test_expired_user_cannot_use_ai(): void
    {
        [$token] = $this->login();
        Customer::query()->update(['trial_ends_at' => now()->subDay()]);
        Settings::set('ai.api_key', 'kn-test');
        $this->postJson('/api/index.php?r=ai/analyze', ['kind' => 'product', 'data' => ['a' => 1]], ['Authorization' => "Bearer $token"])->assertStatus(402);
    }
}
