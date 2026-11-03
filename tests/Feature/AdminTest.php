<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::query()->create(['name' => 'Adm', 'username' => 'adm', 'email' => 'adm@test.id', 'password' => 'rahasia123']);
    }

    public function test_guest_is_redirected_and_login_works(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/pengaturan/ai')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['login' => 'adm', 'password' => 'salah'])->assertSessionHasErrors('login');
        $this->admin();
        $this->post('/admin/login', ['login' => 'adm', 'password' => 'rahasia123'])->assertRedirectContains('/admin');
        $this->get('/admin')->assertOk()->assertSee('Dashboard');
    }

    public function test_login_by_email_and_seeded_admin(): void
    {
        $this->seed();
        $this->post('/admin/login', ['login' => 'rzzkan', 'password' => 'Delova@2026'])->assertRedirectContains('/admin');
        $this->post('/admin/logout');
        $this->post('/admin/login', ['login' => 'rzzkan@gmail.com', 'password' => 'Delova@2026'])->assertRedirectContains('/admin');
        $this->seed();   // seed ulang tidak menimpa / menggandakan
        $this->assertSame(1, User::count());
    }

    public function test_all_pages_render(): void
    {
        $c = Customer::query()->create(['google_sub' => 's', 'email' => 'c@test.id', 'name' => 'C', 'trial_ends_at' => now()->addDays(3)]);
        $this->actingAs($this->admin());
        foreach (['/admin', '/admin/pengguna', "/admin/pengguna/{$c->id}", '/admin/order', '/admin/pemakaian-ai', '/admin/profil',
            '/admin/pengaturan/langganan', '/admin/pengaturan/google', '/admin/pengaturan/midtrans', '/admin/pengaturan/affiliate', '/admin/pengaturan/ai', '/admin/pengaturan/prompt', '/admin/pengaturan/ekstensi'] as $u) {
            $this->get($u)->assertOk();
        }
        $this->get('/admin/pengaturan/zzz')->assertNotFound();
    }

    public function test_affiliate_settings_page_menu_save_and_test(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/pengaturan/midtrans')->assertOk()->assertSee('Affiliate Shopee')->assertSee('/admin/pengaturan/affiliate', false);
        $this->get('/admin/pengaturan/affiliate')->assertOk()->assertSee('Cara menyiapkan Affiliate Shopee')->assertSee('Tes Affiliate')->assertSee('App ID');
        $this->post('/admin/pengaturan/affiliate', ['s' => ['affiliate__enabled' => '1', 'affiliate__app_id' => 'APP9', 'affiliate__secret' => 'SEC-RAHASIA']])->assertRedirect();
        $this->assertTrue(\App\Services\ShopeeAffiliate::configured());
        $this->assertNotSame('SEC-RAHASIA', \App\Models\Setting::query()->where('key', 'affiliate.secret')->value('value'));   // terenkripsi
        \Illuminate\Support\Facades\Http::fake(['*open-api.affiliate*' => \Illuminate\Support\Facades\Http::response(['data' => ['productOfferV2' => ['nodes' => [['itemId' => '1']]]]])]);
        $this->postJson('/admin/pengaturan-tes/affiliate')->assertOk()->assertJsonPath('ok', true);
    }

    public function test_settings_are_saved_secrets_encrypted_and_blank_keeps(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/pengaturan/ai', ['s' => ['ai__api_key' => 'kn-RAHASIA', 'ai__model' => 'model-x', 'ai__enabled' => '1', 'ai__quota__trial' => '5']])->assertRedirect();
        $this->assertSame('kn-RAHASIA', Settings::get('ai.api_key'));
        $this->assertSame('model-x', Settings::get('ai.model'));
        $this->assertSame(5, Settings::int('ai.quota.trial'));
        $stored = \App\Models\Setting::find('ai.api_key')->value;
        $this->assertStringNotContainsString('RAHASIA', $stored);   // terenkripsi di database

        $this->post('/admin/pengaturan/ai', ['s' => ['ai__api_key' => '', 'ai__model' => 'model-x']]);
        $this->assertSame('kn-RAHASIA', Settings::get('ai.api_key'));   // kosong = tetap
        $this->get('/admin/pengaturan/ai')->assertOk()->assertDontSee('kn-RAHASIA');

        $this->post('/admin/pengaturan/ai', ['s' => ['ai__api_key' => ''], 'clear' => ['ai__api_key' => '1']]);
        $this->assertFalse(Settings::has('ai.api_key'));
    }

    public function test_settings_validation_and_google_midtrans(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/pengaturan/google', ['s' => ['google__client_id' => 'salah-format']])->assertSessionHas('error');
        $this->assertFalse(Settings::has('google.client_id'));
        $this->post('/admin/pengaturan/google', ['s' => ['google__client_id' => 'abc-123.apps.googleusercontent.com']]);
        $this->assertSame('abc-123.apps.googleusercontent.com', Settings::get('google.client_id'));
        $this->post('/admin/pengaturan/midtrans', ['s' => ['midtrans__server_key' => 'SB-Mid-server-1', 'midtrans__production' => '0']]);
        $this->assertSame('SB-Mid-server-1', Settings::get('midtrans.server_key'));
        // harga & trial per aplikasi: diatur di Admin -> Aplikasi -> Ubah (Shofee termasuk)
        $base = ['name' => 'Shofee', 'trial_days' => 14, 'sort' => 1, 'active' => 1, 'monthly_price' => 30000, 'monthly_days' => 30, 'yearly_price' => 249000, 'yearly_days' => 365];
        $this->post('/admin/aplikasi/shofee', array_merge($base, ['monthly_price' => 500]))->assertSessionHasErrors('monthly_price');
        $this->post('/admin/aplikasi/shofee', $base)->assertRedirect();
        $this->getJson('/api/index.php?r=plans')->assertJsonPath('plans.monthly.price', 30000)->assertJsonPath('trial_days', 14);
        $this->getJson('/api/index.php?r=plans&app=shofee')->assertJsonPath('plans.yearly.days', 365);
    }

    public function test_prompt_default_is_not_stored_and_custom_is(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/pengaturan/prompt', ['s' => ['ai__prompt__system' => \App\Services\AiPrompts::defaultSystem()]]);
        $this->assertNull(Settings::raw('ai.prompt.system'));
        $this->post('/admin/pengaturan/prompt', ['s' => ['ai__prompt__system' => 'Prompt kustom']]);
        $this->assertSame('Prompt kustom', Settings::raw('ai.prompt.system'));
    }

    public function test_connection_test_buttons(): void
    {
        $this->actingAs($this->admin());
        $this->postJson('/admin/pengaturan-tes/ai')->assertOk()->assertJsonPath('ok', false);
        Settings::set('ai.api_key', 'kn-1');
        Http::fake(['*/chat/completions' => Http::response(['model' => 'm1', 'choices' => [['message' => ['content' => 'OK']]]]),
            '*/models' => Http::response(['data' => [['id' => 'b-model'], ['id' => 'a-model']]])]);
        $this->postJson('/admin/pengaturan-tes/ai')->assertJsonPath('ok', true);
        $this->postJson('/admin/pengaturan-tes/models')->assertJsonPath('ok', true)->assertJsonPath('models', ['a-model', 'b-model']);
        $this->postJson('/admin/pengaturan-tes/midtrans')->assertJsonPath('ok', false);
    }

    public function test_customer_actions_and_logging(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $c = Customer::query()->create(['google_sub' => 's', 'email' => 'c@test.id', 'trial_ends_at' => now()->subDay()]);
        $this->post("/admin/pengguna/{$c->id}", ['action' => 'add_days', 'days' => 10]);
        $this->assertSame('monthly', $c->fresh()->access()['plan']);
        $this->post("/admin/pengguna/{$c->id}", ['action' => 'lifetime']);
        $this->assertTrue($c->fresh()->subscription('shofee')->lifetime);
        $this->post("/admin/pengguna/{$c->id}", ['action' => 'revoke']);
        $this->assertSame('expired', $c->fresh()->access()['plan']);
        $this->assertDatabaseCount('admin_logs', 3);
        $this->get('/admin/pengguna?status=expired&q=c@test')->assertOk()->assertSee('c@test.id');
    }

    public function test_regenerate_key_requires_confirmation(): void
    {
        $this->actingAs($this->admin());
        $old = \App\Services\License::publicKey();
        $this->post('/admin/pengaturan-kunci-lisensi', ['confirm' => 'ya'])->assertSessionHas('error');
        $this->assertSame($old, \App\Services\License::publicKey());
        $this->post('/admin/pengaturan-kunci-lisensi', ['confirm' => 'GANTI KUNCI'])->assertSessionHas('ok');
        $this->assertNotSame($old, \App\Services\License::publicKey());
    }
}
