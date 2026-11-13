<?php

namespace Tests\Feature;

use App\Models\App as AppModel;
use App\Services\Plans;
use App\Models\Customer;
use App\Services\Midtrans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiAppTest extends TestCase
{
    use RefreshDatabase;

    private function app2(): AppModel
    {
        return AppModel::query()->create(['slug' => 'kasirku', 'name' => 'KasirKu', 'trial_days' => 5, 'active' => true, 'sort' => 2,
            'plans' => ['monthly' => ['price' => 15000, 'days' => 30], 'yearly' => ['price' => 0], 'lifetime' => ['enabled' => false, 'price' => 0]]]);
    }

    public function test_subscriptions_are_separate_per_app(): void
    {
        $this->app2();
        $c = Customer::query()->create(['google_sub' => 'm1', 'email' => 'm@test.id', 'trial_ends_at' => now()->addDays(3)]);
        $this->assertSame('trial', $c->access('shofee')['plan']);
        $this->assertSame('none', $c->access('kasirku')['plan']);
        $c->subscription('kasirku', true);
        $this->assertSame('trial', $c->access('kasirku')['plan']);
        $c->subscription('kasirku')->extend(30, 'monthly');
        $this->assertSame('monthly', $c->access('kasirku')['plan']);
        $this->assertSame('trial', $c->access('shofee')['plan']);   // tidak ikut berubah
    }

    public function test_extend_adds_on_top_of_remaining_trial(): void
    {
        $this->app2();
        $c = Customer::query()->create(['google_sub' => 'm9', 'email' => 'm9@test.id', 'trial_ends_at' => now()->addDays(3)]);
        $c->subscription('shofee', true)->extend(30, 'monthly');
        $left = ($c->access('shofee')['until'] - time()) / 86400;
        $this->assertEqualsWithDelta(33, $left, 0.01);   // 3 hari trial + 30 hari paket
        // trial sudah habis: hanya 30 hari
        $d = Customer::query()->create(['google_sub' => 'm8', 'email' => 'm8@test.id', 'trial_ends_at' => now()->subDays(2)]);
        $d->subscription('shofee', true)->extend(30, 'monthly');
        $this->assertEqualsWithDelta(30, ($d->access('shofee')['until'] - time()) / 86400, 0.01);
    }

    public function test_privacy_policy_is_public(): void
    {
        config(['temanseller.contact_email' => 'halo@temanseller.test']);
        $this->get('/privasi')->assertOk()->assertSee('Kebijakan Privasi')->assertSee('halo@temanseller.test')->assertSee('Midtrans');
        $this->get('/privacy')->assertRedirect('/privasi');
        config(['temanseller.contact_email' => '']);
        $this->get('/privasi')->assertOk()->assertDontSee('mailto:');
    }

    public function test_app_url_setting_drives_landing_button(): void
    {
        $k = $this->app2();
        $this->assertSame(url('/akun/aplikasi/kasirku'), $k->landingUrl());              // bawaan
        $k->update(['url' => '/kasirku']);
        $this->assertSame(url('/kasirku'), $k->landingUrl());                           // path relatif
        $k->update(['url' => 'https://kasirku.example.id/']);
        $this->assertSame('https://kasirku.example.id/', $k->landingUrl());             // alamat penuh
        $shofee = AppModel::query()->where('slug', 'shofee')->first();
        $this->assertSame(url('/shofee'), $shofee->landingUrl());                       // diisi migrasi
        $this->get('/')->assertOk()->assertSee('href="https://kasirku.example.id/"', false);
    }

    public function test_admin_can_set_app_url_and_bad_values_are_rejected(): void
    {
        $this->app2();
        $this->actingAs(\App\Models\User::query()->create(['name' => 'Adm', 'username' => 'adm', 'email' => 'adm@test.id', 'password' => 'rahasia123']));
        $base = ['name' => 'KasirKu', 'trial_days' => 5, 'sort' => 2, 'active' => 1, 'monthly_price' => 15000];
        $this->post('/admin/aplikasi/kasirku', $base + ['url' => 'https://temanseller.com/kasirku'])->assertRedirect();
        $this->assertSame('https://temanseller.com/kasirku', AppModel::query()->where('slug', 'kasirku')->value('url'));
        $this->post('/admin/aplikasi/kasirku', $base + ['url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
        $this->post('/admin/aplikasi/kasirku', $base + ['url' => 'http://tidak-aman.test'])->assertSessionHasErrors('url');
        $this->post('/admin/aplikasi/kasirku', $base + ['url' => ''])->assertRedirect();
        $this->assertNull(AppModel::query()->where('slug', 'kasirku')->value('url'));
        $this->get('/admin/aplikasi/kasirku')->assertOk()->assertSee('URL aplikasi');
    }

    public function test_each_app_has_its_own_prices(): void
    {
        $this->app2();
        $this->assertSame(24000, Plans::all('shofee')['monthly']['price']);       // Shofee dari migrasi
        $this->assertSame(15000, Plans::all('kasirku')['monthly']['price']);
        $this->assertArrayNotHasKey('yearly', Plans::all('kasirku'));            // harga 0 = tidak dijual
        $this->assertSame(3, Plans::trialDays('shofee'));
        $this->assertSame(5, Plans::trialDays('kasirku'));
    }

    public function test_download_link_for_shofee_is_shown_and_editable(): void
    {
        $this->assertStringContainsString('chromewebstore.google.com/detail/nnjdmcbildnombgpnoolgopbekipegpa', (string) AppModel::query()->where('slug', 'shofee')->value('download_url'));
        $this->get('/shofee')->assertOk()->assertSee('Pasang di Chrome')->assertSee('chromewebstore.google.com', false);
        $this->get('/')->assertOk()->assertSee('Pasang');
        $this->actingAs(\App\Models\User::query()->create(['name' => 'Adm', 'username' => 'adm', 'email' => 'adm@test.id', 'password' => 'rahasia123']));
        $base = ['name' => 'Shofee', 'trial_days' => 3, 'sort' => 1, 'active' => 1, 'monthly_price' => 24000, 'yearly_price' => 249000];
        $this->post('/admin/aplikasi/shofee', $base + ['download_url' => 'http://tidak-aman.test'])->assertSessionHasErrors('download_url');
        $this->post('/admin/aplikasi/shofee', $base + ['download_url' => ''])->assertRedirect();
        $this->get('/shofee')->assertOk()->assertDontSee('Pasang di Chrome');
    }

    public function test_thanks_page_shows_real_status_not_always_success(): void
    {
        \App\Models\Setting::query()->delete();
        \App\Services\Settings::set('midtrans.server_key', 'SB-Mid-server-x');
        $c = Customer::query()->create(['google_sub' => 't1', 'email' => 't@test.id', 'trial_ends_at' => now()->addDays(3)]);
        $mk = fn ($id, $st = 'pending') => \App\Models\Order::query()->create(['order_id' => $id, 'customer_id' => $c->id, 'app' => 'shofee', 'plan' => 'monthly', 'amount' => 24000, 'status' => $st]);
        $fake = fn ($id, $ts) => \Illuminate\Support\Facades\Http::swap(new \Illuminate\Http\Client\Factory) ?: \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['order_id' => $id, 'status_code' => '200', 'transaction_status' => $ts, 'gross_amount' => '24000.00', 'payment_type' => 'qris'])]);

        $mk('ST-1-cancel'); $fake('ST-1-cancel', 'cancel');
        $this->get('/thanks?order=ST-1-cancel&transaction_status=pending')->assertOk()->assertSee('Pembayaran dibatalkan')->assertDontSee('Terima kasih');
        $this->assertSame('cancelled', \App\Models\Order::query()->where('order_id', 'ST-1-cancel')->value('status'));

        $mk('ST-2-exp'); $fake('ST-2-exp', 'expire');
        $this->get('/thanks?order=ST-2-exp')->assertOk()->assertSee('kedaluwarsa');

        $mk('ST-3-pend'); $fake('ST-3-pend', 'pending');
        $this->get('/thanks?order=ST-3-pend')->assertOk()->assertSee('belum selesai')->assertDontSee('berhasil');

        $mk('ST-4-paid'); $fake('ST-4-paid', 'settlement');
        $this->get('/thanks?order=ST-4-paid')->assertOk()->assertSee('Pembayaran berhasil');
        $this->assertSame('paid', \App\Models\Order::query()->where('order_id', 'ST-4-paid')->value('status'));

        \Illuminate\Support\Facades\Http::swap(new \Illuminate\Http\Client\Factory);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['status_code' => '404'], 404)]);   // transaksi belum dibuat di Midtrans
        $mk('ST-5-none');
        $this->get('/thanks?order=ST-5-none')->assertOk()->assertSee('belum selesai');
        $this->get('/thanks?order=ST-ngawur')->assertOk()->assertSee('tidak ditemukan');
        $this->get('/thanks')->assertOk()->assertSee('tidak ditemukan');
    }

    public function test_payment_extends_only_the_ordered_app(): void
    {
        $this->app2();
        $c = Customer::query()->create(['google_sub' => 'm2', 'email' => 'm2@test.id', 'trial_ends_at' => now()->subDay()]);
        \App\Models\Order::query()->create(['order_id' => 'K-1', 'customer_id' => $c->id, 'app' => 'kasirku', 'plan' => 'monthly', 'amount' => 15000, 'status' => 'pending']);
        Midtrans::markPaid('K-1', 'qris', '{}');
        $this->assertSame('monthly', $c->access('kasirku')['plan']);
        $this->assertSame('expired', $c->access('shofee')['plan']);
    }

    public function test_account_lists_subscribed_apps_and_unknown_app_404(): void
    {
        $this->app2();
        $this->getJson('/api/index.php?r=plans', ['X-App' => 'tidakada'])->assertStatus(404);
        $this->getJson('/api/index.php?r=plans', ['X-App' => 'kasirku'])->assertOk();
    }

    public function test_lifetime_plan_is_shown_on_app_page_even_without_monthly_yearly(): void
    {
        // Aplikasi yang cuma jual Lifetime (mis. Swara) — kartu paketnya tidak boleh hilang.
        AppModel::query()->create(['slug' => 'voiceapp', 'name' => 'VoiceApp', 'trial_days' => 0, 'active' => true, 'sort' => 3,
            'plans' => ['monthly' => ['price' => 0], 'yearly' => ['price' => 0], 'lifetime' => ['enabled' => true, 'price' => 99000]]]);
        $c = Customer::query()->create(['google_sub' => 'lt1', 'email' => 'lt1@test.id', 'trial_ends_at' => now()->subDay()]);
        \App\Services\Settings::set('midtrans.server_key', 'SB-Mid-server-lt');
        $this->withSession(['customer_id' => $c->id])->get('/akun/aplikasi/voiceapp')
            ->assertOk()->assertSee('Lifetime')->assertSee('Rp99.000', false)->assertSee('Beli Lifetime');
    }

    public function test_account_page_has_open_app_button_and_shofee_goes_to_chrome_webstore(): void
    {
        $c = Customer::query()->create(['google_sub' => 'ob1', 'email' => 'ob1@test.id', 'trial_ends_at' => now()->addDays(3)]);
        $this->withSession(['customer_id' => $c->id])->get('/akun')
            ->assertOk()
            ->assertSee('Buka di Chrome Web Store')
            ->assertSee('chromewebstore.google.com', false);
    }

    public function test_hub_lists_apps_and_shofee_landing_by_host_and_path(): void
    {
        $this->app2();
        $this->get('/')->assertOk()->assertSee('TemanSeller')->assertSee('Shofee')->assertSee('KasirKu')->assertSee('Segera hadir')->assertSee('/shofee', false)->assertDontSee('Spill');
        $this->get('/shofee')->assertOk()->assertSee('Rp24.000', false)->assertSee('Shofee')->assertDontSee('Spill Toko');
        \App\Models\App::query()->where('slug', 'kasirku')->update(['active' => false]);
        $this->get('/')->assertOk()->assertDontSee('KasirKu');
        $this->get('http://shofee.localhost/')->assertStatus(301)->assertRedirect(rtrim(config('app.url'), '/') . '/shofee');
    }
}
