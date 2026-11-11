<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ItemSnapshot;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Mengunci perilaku sebelum refaktor performa & abstraksi (Fase 1-2). */
class RefactorSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::query()->create(['name' => 'Adm', 'username' => 'adm', 'email' => 'adm@test.id', 'password' => 'rahasia123']);
    }

    private function queries(callable $fn): int
    {
        DB::flushQueryLog(); DB::enableQueryLog();
        $fn();
        $n = count(DB::getQueryLog()); DB::disableQueryLog();
        return $n;
    }

    private function customer(string $n, array $sub = [], array $legacy = []): Customer
    {
        $c = Customer::query()->create($legacy + ['google_sub' => "s$n", 'email' => "u$n@test.id", 'name' => "User $n", 'trial_ends_at' => now()->subYear()]);
        if ($sub !== []) $c->subscription('shofee', true)->update($sub);
        return $c;
    }

    private function token(Customer $c): string
    {
        $c->subscription('shofee', true)->update(['trial_ends_at' => now()->addDay()]);
        $d = \App\Models\CustomerDevice::query()->create(['customer_id' => $c->id, 'device_hash' => hash('sha256', 'dev-aaaaaaaaaaaaaaaa'), 'label' => 'x', 'last_seen_at' => now()]);
        return Jwt::sign(['sub' => $c->id, 'dev' => $d->id, 'exp' => time() + 3600]);
    }

    public function test_snap_batches_writes_and_stays_idempotent(): void
    {
        $c = $this->customer('1');
        $h = ['Authorization' => 'Bearer ' . $this->token($c)];
        $items = [];
        for ($i = 1; $i <= 149; $i++) $items[] = ['k' => "10.$i", 's' => 100 + $i, 'm' => $i, 'p' => 5000];
        $items[] = ['k' => '10.1', 's' => 999, 'm' => 77];   // kunci ganda: yang terakhir menang

        $n = $this->queries(fn () => $this->postJson('/api/index.php?r=snap', ['items' => $items], $h)->assertOk());
        $this->assertSame(149, ItemSnapshot::count());
        $this->assertSame(77, (int) ItemSnapshot::where('item_key', '10.1')->value('m30'));
        $this->assertLessThan(20, $n, "snap memakai $n query untuk 150 baris");

        $this->postJson('/api/index.php?r=snap', ['items' => [['k' => '10.2', 's' => 5, 'm' => 9]]], $h)->assertOk();
        $this->assertSame(149, ItemSnapshot::count());
        $this->assertSame(9, (int) ItemSnapshot::where('item_key', '10.2')->value('m30'));
        $this->assertSame(5000, (int) ItemSnapshot::where('item_key', '10.3')->value('price'));
    }

    public function test_dashboard_numbers(): void
    {
        $this->actingAs($this->admin());
        $this->customer('a', ['paid_until' => now()->addDays(5), 'trial_ends_at' => now()->subDay()]);                 // bulanan
        $this->customer('b', ['trial_ends_at' => now()->addDays(2)]);                                                    // trial
        $this->customer('c', ['lifetime' => true]);                                                                      // lifetime
        $this->customer('d', ['trial_ends_at' => now()->subDays(3)]);                                                    // berakhir
        $this->customer('e', ['trial_ends_at' => now()->subDays(3), 'paid_until' => now()->subDay()]);                   // berakhir
        $c = Customer::first();
        Order::query()->create(['order_id' => 'D-1', 'customer_id' => $c->id, 'plan' => 'monthly', 'amount' => 24000, 'status' => 'paid', 'paid_at' => now()]);
        Order::query()->create(['order_id' => 'D-2', 'customer_id' => $c->id, 'plan' => 'yearly', 'amount' => 249000, 'status' => 'paid', 'paid_at' => now()->subDays(2)]);
        Order::query()->create(['order_id' => 'D-3', 'customer_id' => $c->id, 'plan' => 'monthly', 'amount' => 24000, 'status' => 'pending']);

        $r = $this->get('/admin')->assertOk();
        $s = $r->viewData('stats');
        $this->assertSame([5, 1, 1, 1, 2, 1], [$s['customers'], $s['trial'], $s['monthly'], $s['lifetime'], $s['expired'], $s['pending']]);
        $this->assertSame(273000, $s['revenue_total']);
        $this->assertSame(273000, array_sum($r->viewData('revenueChart')['series'][0]['data']));
        $this->assertSame(5, array_sum($r->viewData('signupChart')['series'][0]['data']));
        $this->assertCount(30, $r->viewData('signupChart')['categories']);
    }

    public function test_status_counts_match_scope(): void
    {
        $this->customer('a', ['paid_until' => now()->addDays(5)]);
        $this->customer('b', ['trial_ends_at' => now()->addDays(2)]);
        $this->customer('c', ['lifetime' => true]);
        $this->customer('d', ['trial_ends_at' => now()->subDays(3)]);
        $counts = Subscription::statusCounts('shofee');
        foreach (['trial', 'monthly', 'lifetime', 'expired'] as $st) $this->assertSame(Customer::status($st)->count(), $counts[$st], $st);
        $this->assertSame(['trial' => 0, 'monthly' => 0, 'lifetime' => 0, 'expired' => 0], Subscription::statusCounts('tidakada'));
    }

    public function test_customer_list_query_count_does_not_grow_with_rows(): void
    {
        $this->actingAs($this->admin());
        for ($i = 1; $i <= 3; $i++) $this->customer("x$i", ['trial_ends_at' => now()->addDay()]);
        $few = $this->queries(fn () => $this->get('/admin/pengguna')->assertOk());
        for ($i = 4; $i <= 15; $i++) $this->customer("x$i", ['trial_ends_at' => now()->addDay()]);
        $many = $this->queries(fn () => $this->get('/admin/pengguna')->assertOk()->assertSee('u'));
        $this->assertSame($few, $many, "query tumbuh dari $few ke $many");
        $detail = $this->queries(fn () => $this->get('/admin/pengguna/' . Customer::first()->id)->assertOk());
        $this->assertLessThan(30, $detail);
    }

    public function test_reading_access_never_writes(): void
    {
        $c = $this->customer('r', [], ['trial_ends_at' => now()->addDays(2)]);
        $this->assertSame(0, Subscription::count());
        $this->assertSame('trial', $c->access()['plan']);
        $this->assertSame('none', $c->access('tidakada')['plan']);
        $l = $this->customer('l', [], ['lifetime' => true]);
        $this->assertSame('lifetime', $l->access()['plan']);
        $this->assertSame(0, Subscription::count());   // baca tidak menulis
        $l->subscription('shofee', true);               // baru ditulis saat diminta
        $this->assertSame(1, Subscription::count());
    }

    public function test_new_google_login_creates_shofee_subscription(): void
    {
        $c = Customer::fromGoogle(['sub' => 'gx', 'email' => 'gx@test.id', 'name' => 'G']);
        $this->assertSame(1, Subscription::where('customer_id', $c->id)->where('app', 'shofee')->count());
        $this->assertSame('trial', $c->access()['plan']);
    }

    public function test_migration_backfills_legacy_customers(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 11, '--force' => true])->assertExitCode(0);   // swara (11-11) + harga coret (11-06) + audit (11-06) + layanan (11-06) + 11-04 (unduh) + 000700 (harga) + 000600 (url) + 000500 (index) + 000400 (apps & subscriptions)
        foreach ([['t', ['trial_ends_at' => now()->addDays(2)]], ['m', ['paid_until' => now()->addDays(9), 'paid_plan' => 'yearly']], ['l', ['lifetime' => true]], ['e', ['trial_ends_at' => now()->subDay()]]] as [$n, $legacy]) {
            Customer::query()->create($legacy + ['google_sub' => "s$n", 'email' => "$n@test.id", 'trial_ends_at' => now()->subYear()]);
        }
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->assertSame(4, Subscription::count());
        $by = fn ($n) => Customer::where('email', "$n@test.id")->first()->access();
        $this->assertSame('trial', $by('t')['plan']);
        $this->assertSame(['monthly', 'yearly'], [$by('m')['plan'], $by('m')['tier']]);
        $this->assertSame('lifetime', $by('l')['plan']);
        $this->assertSame('expired', $by('e')['plan']);
        $this->assertSame('shofee', \App\Models\App::first()->slug);
    }

    public function test_commands_use_new_names_and_keep_old_aliases(): void
    {
        $all = \Illuminate\Support\Facades\Artisan::all();
        foreach (['install', 'admin', 'doctor'] as $n) {
            $this->assertArrayHasKey("temanseller:$n", $all);
            $this->assertArrayHasKey("spill:$n", $all);   // alias nama lama
        }
        $this->assertSame('1', (string) (int) (config('temanseller.max_devices') >= 1));
    }
}
