<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private function webLogin(string $nonceOverride = null): \Illuminate\Testing\TestResponse
    {
        Settings::set('google.client_id', 'cid.apps.googleusercontent.com');
        $this->get('/masuk')->assertOk();
        $nonce = session('portal_nonce');
        Http::fake(['*tokeninfo*' => Http::response(['aud' => 'cid.apps.googleusercontent.com', 'iss' => 'accounts.google.com', 'exp' => time() + 600,
            'sub' => 'g9', 'email' => 'web@user.test', 'email_verified' => 'true', 'name' => 'Sari', 'nonce' => $nonceOverride ?? $nonce])]);
        return $this->postJson('/masuk/google', ['credential' => 'tok']);
    }

    public function test_guest_redirected_and_login_page_states(): void
    {
        $this->get('/akun')->assertRedirect('/masuk');
        $this->get('/masuk')->assertOk()->assertSee('belum dikonfigurasi');
        Settings::set('google.client_id', 'cid.apps.googleusercontent.com');
        $this->get('/masuk')->assertOk()->assertSee('g_id_onload', false)->assertSee('cid.apps.googleusercontent.com');
    }

    public function test_google_login_creates_trial_account_and_shows_status(): void
    {
        $this->webLogin()->assertOk()->assertJsonPath('redirect', url('/akun'));
        $this->assertSame(1, Customer::count());
        $this->get('/akun')->assertOk()->assertSee('Trial')->assertSee('web@user.test')->assertSee('Berlangganan Bulanan');
    }

    public function test_wrong_nonce_is_rejected(): void
    {
        $this->webLogin('nonce-lain')->assertStatus(401);
        $this->get('/akun')->assertRedirect('/masuk');
    }

    public function test_pay_creates_order_and_redirects_to_midtrans(): void
    {
        $this->webLogin()->assertOk();
        Settings::set('midtrans.server_key', 'SB-Mid-server-1');
        Http::fake(['*snap/v1/transactions' => Http::response(['token' => 'T', 'redirect_url' => 'https://pay.test/snap'], 201)]);
        $this->post('/akun/bayar', ['plan' => 'monthly'])->assertRedirect('https://pay.test/snap');
        $this->assertDatabaseHas('orders', ['plan' => 'monthly', 'amount' => 25000, 'status' => 'pending', 'snap_token' => 'T']);
        $this->post('/akun/bayar', ['plan' => 'nope'])->assertSessionHas('error');
    }

    public function test_lifetime_user_sees_lifetime_and_cannot_rebuy(): void
    {
        $this->webLogin()->assertOk();
        Customer::query()->update(['lifetime' => true]);
        Settings::set('midtrans.server_key', 'SB-Mid-server-1');
        $this->get('/akun')->assertOk()->assertSee('Selamanya')->assertSee('Anda sudah Lifetime');
        $this->post('/akun/bayar', ['plan' => 'lifetime'])->assertSessionHas('error');
        $this->assertSame(0, Order::count());
    }

    public function test_logout_clears_session(): void
    {
        $this->webLogin()->assertOk();
        $this->post('/keluar')->assertRedirect('/');
        $this->get('/akun')->assertRedirect('/masuk');
    }
}
