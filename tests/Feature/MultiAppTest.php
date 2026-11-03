<?php

namespace Tests\Feature;

use App\Models\App as AppModel;
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
