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
