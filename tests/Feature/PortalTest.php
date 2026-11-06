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
        $this->get('/login')->assertOk();
        $nonce = session('portal_nonce');
        Http::fake(['*tokeninfo*' => Http::response(['aud' => 'cid.apps.googleusercontent.com', 'iss' => 'accounts.google.com', 'exp' => time() + 600,
            'sub' => 'g9', 'email' => 'web@user.test', 'email_verified' => 'true', 'name' => 'Sari', 'nonce' => $nonceOverride ?? $nonce])]);
        return $this->postJson('/login/google', ['credential' => 'tok']);
    }

    public function test_guest_redirected_and_login_page_states(): void
    {
        $this->get('/akun')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('belum dikonfigurasi');
        Settings::set('google.client_id', 'cid.apps.googleusercontent.com');
        $this->get('/login')->assertOk()->assertSee('g_id_onload', false)->assertSee('cid.apps.googleusercontent.com');
    }

    public function test_google_login_creates_trial_account_and_shows_status(): void
    {
        $this->webLogin()->assertOk()->assertJsonPath('redirect', url('/akun'));
        $this->assertSame(1, Customer::count());
        $this->get('/akun')->assertOk()->assertSee('Trial')->assertSee('web@user.test')->assertSee('Shofee');
        $this->get('/akun/aplikasi/shofee')->assertOk()->assertSee('Pilih Bulanan');
    }

    public function test_revoke_device_from_portal(): void
    {
        $this->webLogin()->assertOk();
        $c = Customer::first();
        $d = \App\Models\CustomerDevice::create(['customer_id' => $c->id, 'device_hash' => str_repeat('a', 64), 'label' => 'Chrome · Windows', 'last_seen_at' => now()]);
        $other = Customer::create(['google_sub' => 'x', 'email' => 'o@o.test', 'trial_ends_at' => now()]);
        $od = \App\Models\CustomerDevice::create(['customer_id' => $other->id, 'device_hash' => str_repeat('b', 64)]);
        $this->get('/akun')->assertOk()->assertSee('Browser terhubung')->assertSee('Chrome · Windows');
        $this->delete('/akun/browser/' . $od->id)->assertSessionHas('error');   // milik akun lain: tidak bisa
        $this->assertDatabaseHas('customer_devices', ['id' => $od->id]);
        $this->delete('/akun/browser/' . $d->id)->assertSessionHas('ok');
        $this->assertDatabaseMissing('customer_devices', ['id' => $d->id]);
    }

    public function test_wrong_nonce_is_rejected(): void
    {
        $this->webLogin('nonce-lain')->assertStatus(401);
        $this->get('/akun')->assertRedirect('/login');
    }

    public function test_pay_creates_order_and_redirects_to_midtrans(): void
    {
        $this->webLogin()->assertOk();
        Settings::set('midtrans.server_key', 'SB-Mid-server-1');
        Http::fake(['*snap/v1/transactions' => Http::response(['token' => 'T', 'redirect_url' => 'https://pay.test/snap'], 201)]);
        $this->post('/akun/bayar', ['plan' => 'monthly'])->assertRedirect('https://pay.test/snap');
        $this->assertDatabaseHas('orders', ['plan' => 'monthly', 'amount' => 24000, 'status' => 'pending', 'snap_token' => 'T']);
        $this->post('/akun/bayar', ['plan' => 'nope'])->assertSessionHas('error');
    }

    public function test_lifetime_user_sees_lifetime_and_cannot_rebuy(): void
    {
        $this->webLogin()->assertOk();
        Customer::first()->subscription('shofee', true)->update(['lifetime' => true]);
        Settings::set('midtrans.server_key', 'SB-Mid-server-1');
        $this->get('/akun')->assertOk()->assertSee('Selamanya');
        $this->get('/akun/aplikasi/shofee')->assertOk()->assertSee('Kamu memiliki akses Lifetime');
        $this->post('/akun/aplikasi/shofee/bayar', ['plan' => 'lifetime'])->assertSessionHas('error');
        $this->assertSame(0, Order::count());
    }

    public function test_buy_link_guest_goes_login_then_straight_to_midtrans(): void
    {
        Settings::set('google.client_id', 'cid.apps.googleusercontent.com');
        Settings::set('midtrans.server_key', 'SB-Mid-server-1');
        Http::fake(['*snap/v1/transactions' => Http::response(['token' => 'T', 'redirect_url' => 'https://pay.test/yr'], 201)]);
        $this->get('/beli/yearly')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertDontSee('dipilih');
        $this->webLogin()->assertOk()->assertJsonPath('redirect', url('/beli-lanjut'));
        $this->get('/beli-lanjut')->assertRedirect('https://pay.test/yr');
        $this->assertDatabaseHas('orders', ['plan' => 'yearly', 'amount' => 249000, 'status' => 'pending']);
    }

    public function test_buy_link_when_logged_in_and_unknown_plan(): void
    {
        $this->webLogin()->assertOk();
        Settings::set('midtrans.server_key', 'SB-Mid-server-1');
        Http::fake(['*snap/v1/transactions' => Http::response(['token' => 'T', 'redirect_url' => 'https://pay.test/m'], 201)]);
        $this->get('/beli/monthly')->assertRedirect(route('portal.buy.go'));
        $this->get('/beli-lanjut')->assertRedirect('https://pay.test/m');
        $this->get('/beli/zzz')->assertRedirect('/#harga');
    }

    public function test_landing_page_shows_prices_and_buy_links(): void
    {
        $this->get('/shofee')->assertOk()->assertSee('Rp24.000', false)->assertSee('Rp49.000', false)->assertSee('Rp249.000', false)->assertSee('Rp549.000', false)
            ->assertSee('img/preview/toko-analisa-ai.jpg', false)->assertSee('/beli/monthly', false)->assertSee('/beli/yearly', false)->assertSee('3 hari', false);
    }

    public function test_yearly_payment_extends_365_days_and_labels_tier(): void
    {
        $this->webLogin()->assertOk();
        $c = Customer::first();
        $o = Order::query()->create(['order_id' => 'ST-Y', 'customer_id' => $c->id, 'plan' => 'yearly', 'amount' => 249000, 'status' => 'pending']);
        \App\Services\Midtrans::markPaid('ST-Y', 'qris', '{}');
        $s = $c->subscription('shofee');
        $this->assertSame('yearly', $s->paid_plan);
        $this->assertTrue($s->paid_until->greaterThan(now()->addDays(364)));
        $this->get('/akun')->assertSee('Tahunan');
    }

    public function test_ai_daily_quota_is_dynamic_from_admin_settings(): void
    {
        Settings::set('ai.enabled', '1'); Settings::set('ai.api_key', 'kn-x', true);
        Settings::set('ai.quota.trial', '7'); Settings::set('ai.quota.monthly', '55'); Settings::set('ai.quota.yearly', '0');
        $this->get('/shofee')->assertSee('7x/hari', false)->assertSee('55x/hari', false)->assertSee('tanpa batas/hari', false);
        $this->getJson('/api/index.php?r=plans')->assertJsonPath('plans.monthly.ai_limit', 55)->assertJsonPath('plans.yearly.ai_limit', null);
        $this->webLogin()->assertOk();
        $this->get('/akun/aplikasi/shofee')->assertOk()->assertSee('55 analisis/hari', false);
    }

    public function test_landing_has_no_raw_blade_directives(): void
    {
        foreach ([false, true] as $aiOn) {
            if ($aiOn) { Settings::set('ai.enabled', '1'); Settings::set('ai.api_key', 'kn-x', true); }
            $html = $this->get('/')->assertOk()->getContent() . $this->get('/shofee')->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression('/@(if|endif|else|foreach|endforeach|php)\b|\{\{|\{!!/', $html);
        }
    }

    public function test_logout_clears_session(): void
    {
        $this->webLogin()->assertOk();
        $this->post('/keluar')->assertRedirect('/');
        $this->get('/akun')->assertRedirect('/login');
    }
}
