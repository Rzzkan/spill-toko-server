<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServicePlan;
use App\Models\ServicePortfolio;
use App\Models\ServiceSubscription;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LayananTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::query()->create(['name' => 'Adm', 'username' => 'adm', 'email' => 'adm@test.id', 'password' => 'rahasia123']);
    }

    private function webLogin(): void
    {
        Settings::set('google.client_id', 'cid.apps.googleusercontent.com');
        $this->get('/login')->assertOk();
        Http::fake(['*tokeninfo*' => Http::response(['aud' => 'cid.apps.googleusercontent.com', 'iss' => 'accounts.google.com', 'exp' => time() + 600,
            'sub' => 'g9', 'email' => 'web@user.test', 'email_verified' => 'true', 'name' => 'Sari', 'nonce' => session('portal_nonce')])]);
        $this->postJson('/login/google', ['credential' => 'tok'])->assertOk();
    }

    private function livePlan(int $price = 150000): ServicePlan
    {
        $type = ServiceType::query()->where('slug', 'ig-dibantu-posting')->first();
        return ServicePlan::query()->create(['service_type_id' => $type->id, 'name' => 'Growth', 'price' => $price, 'period' => '/bulan', 'features' => "A\nB", 'is_active' => true, 'duration_days' => 30]);
    }

    public function test_public_page_shows_empty_state_without_active_plans(): void
    {
        ServicePlan::query()->update(['is_active' => false]);
        $this->get('/layanan')->assertOk()->assertSee('Paket sedang disiapkan')->assertSee('Portofolio akan segera');
    }

    public function test_public_page_lists_active_plans_and_portfolio(): void
    {
        $this->livePlan();
        ServicePortfolio::create(['title' => 'Feed Toko Hijab', 'result_text' => 'Followers +2rb', 'is_active' => true, 'link_url' => 'https://instagram.com/x']);
        ServicePortfolio::create(['title' => 'Tersembunyi', 'is_active' => false]);
        $this->get('/layanan')->assertOk()->assertSee('Growth')->assertSee('Rp150.000')->assertSee('Feed Toko Hijab')->assertDontSee('Tersembunyi');
    }

    public function test_service_can_be_switched_off(): void
    {
        Settings::set('layanan.enabled', '0');
        $this->get('/layanan')->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('Layanan kelola sosmed');
    }

    public function test_hub_links_service_and_labels_shofee_as_chrome_extension(): void
    {
        $this->get('/')->assertOk()->assertSee('Shofee')->assertSee('Extension Chrome')->assertSee(route('layanan'));
    }

    public function test_order_flow_creates_pending_subscription_and_shows_in_account(): void
    {
        $plan = $this->livePlan();
        $this->get('/layanan/pesan/' . $plan->id)->assertRedirect('/login');
        $this->webLogin();
        $this->get('/layanan/pesan/' . $plan->id)->assertRedirect(route('portal.layanan'));
        $this->get('/akun/layanan')->assertOk()->assertSee('Konfirmasi pesanan');
        $this->post('/akun/layanan/pesan', ['plan' => $plan->id, 'note' => '@tokoku'])->assertRedirect(route('portal.layanan'));
        $sub = ServiceSubscription::first();
        $this->assertSame('pending', $sub->status);
        $this->assertSame(150000, $sub->price);
        $this->post('/akun/layanan/pesan', ['plan' => $plan->id])->assertSessionHas('ok');   // tidak ganda
        $this->assertSame(1, ServiceSubscription::count());
        $this->get('/akun/layanan')->assertOk()->assertSee('Menunggu konfirmasi')->assertSee('Growth');
        $this->get('/akun')->assertOk()->assertSee('Layanan Sosmed');
    }

    public function test_inactive_or_free_plan_cannot_be_ordered(): void
    {
        $this->webLogin();
        $draft = ServicePlan::query()->first();
        $draft->update(['is_active' => false]);
        $this->post('/akun/layanan/pesan', ['plan' => $draft->id])->assertSessionHas('error');
        $this->assertSame(0, ServiceSubscription::count());
    }

    public function test_customer_sees_drive_url_only_for_own_subscription_and_only_https(): void
    {
        $this->webLogin();
        $me = Customer::first();
        $other = Customer::create(['google_sub' => 'z', 'email' => 'z@z.test', 'trial_ends_at' => now()]);
        ServiceSubscription::create(['customer_id' => $me->id, 'plan_name' => 'Combo – Growth', 'price' => 1, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(10), 'drive_url' => 'https://drive.google.com/drive/folders/abc']);
        ServiceSubscription::create(['customer_id' => $other->id, 'plan_name' => 'Rahasia', 'price' => 1, 'status' => 'active', 'drive_url' => 'https://drive.google.com/drive/folders/secret']);
        $this->get('/akun/layanan')->assertOk()->assertSee('drive/folders/abc')->assertSee('Aktif')->assertDontSee('secret')->assertDontSee('Rahasia');
        ServiceSubscription::where('customer_id', $me->id)->update(['drive_url' => 'javascript:alert(1)']);
        $this->get('/akun/layanan')->assertOk()->assertDontSee('javascript:');
    }

    public function test_expired_active_subscription_reads_as_expired(): void
    {
        $s = new ServiceSubscription(['status' => 'active', 'ends_at' => now()->subDay()]);
        $this->assertSame('expired', $s->effectiveStatus());
    }

    public function test_note_cannot_touch_other_customers_subscription(): void
    {
        $this->webLogin();
        $other = Customer::create(['google_sub' => 'z', 'email' => 'z@z.test', 'trial_ends_at' => now()]);
        $s = ServiceSubscription::create(['customer_id' => $other->id, 'plan_name' => 'X', 'price' => 1, 'status' => 'pending']);
        $this->post('/akun/layanan/' . $s->id . '/catatan', ['note' => 'hack'])->assertNotFound();
    }

    public function test_admin_pages_require_login(): void
    {
        $this->get('/admin/layanan/paket')->assertRedirect();
        $this->post('/admin/layanan/portofolio', ['title' => 'x'])->assertRedirect();
    }

    public function test_admin_manages_types_and_plans_dynamically(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/layanan/paket')->assertOk()->assertSee('Coba Sendiri');
        $this->post('/admin/layanan/jenis', ['name' => 'TikTok', 'sort' => 4, 'is_active' => 1])->assertRedirect();
        $type = ServiceType::where('slug', 'tiktok')->first();
        $this->assertNotNull($type);
        $this->post('/admin/layanan/paket', ['service_type_id' => $type->id, 'name' => 'Basic', 'price' => 99000, 'duration_days' => 30, 'is_active' => 1, 'features' => "x\ny"])->assertRedirect();
        $this->get('/layanan')->assertSee('TikTok')->assertSee('Rp99.000');
        $plan = ServicePlan::where('name', 'Basic')->first();
        $this->post('/admin/layanan/paket/' . $plan->id, ['service_type_id' => $type->id, 'name' => 'Basic', 'price' => 120000, 'duration_days' => 30, 'is_active' => 1])->assertRedirect();
        $this->get('/layanan')->assertSee('Rp120.000');
        $this->post('/admin/layanan/paket/' . $plan->id, ['service_type_id' => $type->id, 'name' => 'Basic', 'price' => 0, 'duration_days' => 30, 'is_active' => 1])->assertSessionHas('error');   // aktif harus berharga
        $this->post('/admin/layanan/jenis/' . $type->id . '/hapus')->assertSessionHas('error');                                // masih punya paket
        $this->post('/admin/layanan/paket/' . $plan->id . '/hapus')->assertSessionHas('ok');
        $this->post('/admin/layanan/jenis/' . $type->id . '/hapus')->assertSessionHas('ok');
        $this->assertNull(ServiceType::where('slug', 'tiktok')->first());
    }

    public function test_admin_portfolio_upload_serve_and_delete(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin());
        $this->post('/admin/layanan/portofolio', ['title' => 'Kasus A', 'link_url' => 'javascript:x', 'is_active' => 1])->assertSessionHasErrors('link_url');
        $this->post('/admin/layanan/portofolio', ['title' => 'Kasus A', 'platform' => 'Instagram', 'is_active' => 1,
            'image' => UploadedFile::fake()->image('a.jpg', 600, 400)])->assertRedirect();
        $p = ServicePortfolio::first();
        $this->assertNotNull($p->image_path);
        Storage::disk('local')->assertExists($p->image_path);
        $this->get($p->imageUrl())->assertOk();
        $this->post('/admin/layanan/portofolio', ['title' => 'Bukan gambar', 'image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('image');
        $this->post('/admin/layanan/portofolio/' . $p->id . '/hapus')->assertSessionHas('ok');
        Storage::disk('local')->assertMissing($p->image_path);
        $this->assertSame(0, ServicePortfolio::count());
    }

    public function test_admin_activates_subscription_and_customer_sees_drive(): void
    {
        $plan = $this->livePlan();
        $this->webLogin();
        $this->post('/akun/layanan/pesan', ['plan' => $plan->id]);
        $sub = ServiceSubscription::first();
        $this->actingAs($this->admin());
        $this->post('/admin/layanan/langganan/' . $sub->id, ['status' => 'active', 'drive_url' => 'http://insecure', 'plan_name' => 'Combo – Growth'])->assertSessionHasErrors('drive_url');
        $this->post('/admin/layanan/langganan/' . $sub->id, ['status' => 'active', 'drive_url' => 'https://drive.google.com/drive/folders/ok1', 'plan_name' => 'Combo – Growth'])->assertRedirect();
        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->ends_at);                       // otomatis 30 hari
        $this->get('/admin/layanan/langganan')->assertOk()->assertSee('web@user.test');
    }

    public function test_admin_can_add_subscription_manually_by_email(): void
    {
        Customer::create(['google_sub' => 'q', 'email' => 'q@q.test', 'trial_ends_at' => now()]);
        $this->actingAs($this->admin());
        $this->post('/admin/layanan/langganan', ['email' => 'nobody@x.test', 'status' => 'active'])->assertSessionHas('error');
        $this->post('/admin/layanan/langganan', ['email' => 'q@q.test', 'status' => 'active', 'plan_name' => 'Kustom', 'price' => 500000])->assertRedirect();
        $this->assertSame('Kustom', ServiceSubscription::first()->plan_name);
    }

    public function test_whatsapp_link_comes_from_settings(): void
    {
        $this->get('/layanan')->assertDontSee('wa.me');
        Settings::set('layanan.wa_number', '+62 812-3456-7890');
        $this->get('/layanan')->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_public_page_uses_tabs_and_short_price(): void
    {
        $this->livePlan(2500000);
        $this->get('/layanan')->assertOk()->assertSee('data-tab="ig-dibantu-posting"', false)->assertSee('Rp2,5 jt')->assertSee('role="tabpanel"', false);
        $this->assertSame('Rp1,5 jt', \App\Support\Fmt::rpShort(1500000));
        $this->assertSame('Rp3 jt', \App\Support\Fmt::rpShort(3000000));
        $this->assertSame('Rp150.000', \App\Support\Fmt::rpShort(150000));
    }

    public function test_seeded_packages_and_discount_display(): void
    {
        $this->assertSame(4, ServiceType::query()->count());
        $this->assertSame(8, ServicePlan::query()->where('is_active', true)->count());
        $p = ServicePlan::query()->where('name', 'Naik Kelas')->whereHas('type', fn ($q) => $q->where('slug', 'ig-dibantu-posting'))->first();
        $this->assertSame(2000000, $p->price);
        $this->assertSame(4000000, $p->original_price);
        $this->assertSame(50, $p->discountPct());
        $this->get('/layanan')->assertOk()->assertSee('IG Tanpa Posting')->assertSee('Threads Dibantu Posting')->assertSee('Hemat 50%')->assertSee('Rp4 jt')->assertSee('Add-on:')->assertSee('180 konten Feed');
    }

    public function test_admin_can_set_original_price_and_addon(): void
    {
        $this->actingAs($this->admin());
        $p = ServicePlan::query()->first();
        $this->post('/admin/layanan/paket/' . $p->id, ['service_type_id' => $p->service_type_id, 'name' => $p->name, 'price' => 300000, 'original_price' => 600000, 'addon' => 'Tambah desain Rp10.000', 'duration_days' => 30, 'is_active' => 1])->assertRedirect();
        $p->refresh();
        $this->assertSame([600000, 'Tambah desain Rp10.000'], [$p->original_price, $p->addon]);
    }

    public function test_coming_soon_shown_and_configurable_and_single_menu(): void
    {
        $this->get('/')->assertOk()->assertSee('Kelola Social Media')->assertSee('Segera hadir')->assertSee('Kelola TikTok')->assertDontSee('Threads Tanpa Posting');
        $this->get('/layanan')->assertOk()->assertSee('Segera hadir:')->assertSee('Kelola TikTok');
        Settings::set('layanan.coming_soon', 'Edit Video, Content Plan');
        $this->assertSame(['Edit Video', 'Content Plan'], \App\Services\Layanan::comingSoon());
        $this->get('/layanan')->assertSee('Edit Video')->assertDontSee('Kelola TikTok');
        Settings::set('layanan.coming_soon', '');
        $this->get('/layanan')->assertDontSee('Segera hadir:');
    }
}
