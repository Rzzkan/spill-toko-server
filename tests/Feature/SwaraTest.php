<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\Settings;
use App\Services\Swara;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SwaraTest extends TestCase
{
    use RefreshDatabase;

    private function webLogin(string $email = 'web@user.test'): Customer
    {
        Settings::set('google.client_id', 'cid.apps.googleusercontent.com');
        $this->get('/login')->assertOk();
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*tokeninfo*' => Http::response(['aud' => 'cid.apps.googleusercontent.com', 'iss' => 'accounts.google.com', 'exp' => time() + 600,
            'sub' => 'g' . crc32($email), 'email' => $email, 'email_verified' => 'true', 'name' => 'Sari', 'nonce' => session('portal_nonce')])]);
        $this->postJson('/login/google', ['credential' => 'tok'])->assertOk();
        return Customer::query()->where('email', $email)->first();
    }

    private function giveLifetime(Customer $c): void
    {
        $c->subscriptions()->updateOrCreate(['app' => 'swara'], ['lifetime' => true]);
    }

    public function test_landing_page_renders(): void
    {
        $this->get('/swara')->assertOk()->assertSee('Swara');
    }

    public function test_pubkey_endpoint_is_public_and_stable(): void
    {
        $r1 = $this->getJson('/api/swara/pubkey')->assertOk()->json('pubkey');
        $r2 = $this->getJson('/api/swara/pubkey')->assertOk()->json('pubkey');
        $this->assertNotEmpty($r1);
        $this->assertSame($r1, $r2);   // kunci sama antar permintaan (disimpan, bukan dibuat ulang)
    }

    public function test_tool_page_redirects_without_license(): void
    {
        $this->webLogin();
        $this->get('/akun/swara')->assertRedirect('/swara');
    }

    public function test_guide_page_requires_license_and_shows_placeholder_videos(): void
    {
        $c = $this->webLogin();
        $this->get('/akun/swara/panduan')->assertRedirect('/swara');   // belum punya lisensi
        $this->giveLifetime($c);
        $this->get('/akun/swara/panduan')->assertOk()->assertSee('Voice Cloning')->assertSee('Video segera hadir');
    }

    public function test_guide_page_shows_configured_video_links(): void
    {
        $c = $this->webLogin();
        $this->giveLifetime($c);
        Settings::set('swara.video_clone', 'https://youtube.com/watch?v=abc');
        $this->get('/akun/swara/panduan')->assertOk()->assertSee('https://youtube.com/watch?v=abc', false);
    }

    public function test_pair_requires_license(): void
    {
        $this->webLogin();
        $this->postJson('/akun/swara/pair', ['sid' => 'abc123'])->assertStatus(402);
    }

    public function test_pair_and_token_round_trip(): void
    {
        $c = $this->webLogin();
        $this->giveLifetime($c);
        $this->get('/akun/swara')->assertOk();

        $this->postJson('/akun/swara/pair', ['sid' => 'abc123'])->assertOk()->assertJson(['ok' => true]);
        $tok = $this->postJson('/akun/swara/token', ['sid' => 'abc123'])->assertOk()->json();
        $this->assertNotEmpty($tok['token']);
        $this->assertGreaterThan(time(), $tok['exp']);

        // Tanda tangan harus valid terhadap kunci publik yang dilayani /api/swara/pubkey.
        [$payloadB64, $sigB64] = explode('.', $tok['token']);
        $payload = base64_decode($payloadB64);
        $pub = base64_decode($this->getJson('/api/swara/pubkey')->json('pubkey'));
        $this->assertTrue(sodium_crypto_sign_verify_detached(base64_decode($sigB64), $payload, $pub));
        $this->assertSame('abc123', json_decode($payload, true)['sid']);
    }

    public function test_new_pairing_overrides_old_session(): void
    {
        $c = $this->webLogin();
        $this->giveLifetime($c);
        $this->postJson('/akun/swara/pair', ['sid' => 'old111'])->assertOk();
        $this->postJson('/akun/swara/token', ['sid' => 'old111'])->assertOk();   // sesi lama masih berlaku

        $this->postJson('/akun/swara/pair', ['sid' => 'new222'])->assertOk();    // notebook baru dijalankan
        $this->postJson('/akun/swara/token', ['sid' => 'old111'])->assertStatus(409);   // sesi lama ditolak
        $this->postJson('/akun/swara/token', ['sid' => 'new222'])->assertOk();          // sesi baru tetap jalan
    }

    public function test_invalid_sid_rejected(): void
    {
        $c = $this->webLogin();
        $this->giveLifetime($c);
        $this->postJson('/akun/swara/pair', ['sid' => 'a'])->assertStatus(422);   // terlalu pendek
        $this->postJson('/akun/swara/pair', ['sid' => 'has space!'])->assertStatus(422);
    }

    public function test_expired_lifetime_revoked_by_admin_loses_access(): void
    {
        $c = $this->webLogin();
        $this->giveLifetime($c);
        $this->postJson('/akun/swara/pair', ['sid' => 'abc123'])->assertOk();
        $c->subscriptions()->where('app', 'swara')->update(['lifetime' => false]);
        $this->postJson('/akun/swara/token', ['sid' => 'abc123'])->assertStatus(402);
    }
}
