<?php

namespace Tests\Feature;

use App\Models\AuditScan;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Audit;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditTest extends TestCase
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

    private function productPayload(): array
    {
        return ['status' => 'ok', 'product' => ['name' => 'Kebaya Modern Brokat Premium Wanita Bahan Halus Nyaman Dipakai Acara Formal Wisuda', 'price' => 185000, 'images' => 7, 'has_video' => true,
            'description_len' => 620, 'attributes' => 8, 'rating' => 4.8, 'rating_count' => 120, 'stock' => 40, 'variations' => 3, 'free_shipping' => true, 'discount_pct' => 10,
            'shop' => ['name' => 'Delova', 'response_rate' => 95]], 'reviews' => [['rating' => 5, 'text' => 'Bagus']], 'junk' => 'x'];
    }

    private function aiJson(): array
    {
        return ['choices' => [['message' => ['content' => json_encode(['ringkasan' => 'Produk sehat.', 'skor' => 80, 'label' => 'Potensial', 'temuan' => ['a'], 'peluang' => ['b'], 'risiko' => ['c'],
            'rekomendasi' => [['aksi' => 'Tambah video', 'alasan' => 'x', 'prioritas' => 'tinggi']], 'kata_kunci' => ['kebaya'], 'saran_judul' => ['Judul 1']])]]], 'model' => 'm', 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20]];
    }

    private function aiOn(): void { Settings::set('ai.api_key', 'kn-test'); Settings::set('ai.enabled', '1'); }

    public function test_parse_url(): void
    {
        $this->assertSame(['product', 11, 22], [Audit::parseUrl('https://shopee.co.id/Kebaya-Cantik-i.11.22?sp=1')['kind'], Audit::parseUrl('https://shopee.co.id/Kebaya-Cantik-i.11.22')['shopid'], Audit::parseUrl('shopee.co.id/product/11/22')['itemid']]);
        $this->assertSame('shop', Audit::parseUrl('https://shopee.co.id/delova.id')['kind']);
        $this->assertSame(55, Audit::parseUrl('https://shopee.co.id/shop/55')['shopid']);
        $this->assertNull(Audit::parseUrl('https://evil.com/shopee.co.id/x-i.1.2'));
        $this->assertNull(Audit::parseUrl('https://shopee.co.id.evil.com/user'));
        $this->assertNull(Audit::parseUrl('https://shopee.co.id/cart'));
        $this->assertNull(Audit::parseUrl('javascript:alert(1)'));
    }

    public function test_requires_login_and_remembers_destination(): void
    {
        $this->get('/audit')->assertRedirect('/login');
        $this->assertSame('/audit', session('login_next'));
        $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/x'])->assertRedirect();
    }

    public function test_login_redirects_back_to_audit(): void
    {
        $this->get('/audit');
        Settings::set('google.client_id', 'cid.apps.googleusercontent.com');
        $this->get('/login');
        Http::fake(['*tokeninfo*' => Http::response(['aud' => 'cid.apps.googleusercontent.com', 'iss' => 'accounts.google.com', 'exp' => time() + 600, 'sub' => 'g1', 'email' => 'a@b.test', 'email_verified' => 'true', 'name' => 'A', 'nonce' => session('portal_nonce')])]);
        $this->postJson('/login/google', ['credential' => 't'])->assertOk()->assertJson(['redirect' => route('audit')]);
    }

    public function test_full_flow_counts_only_on_success(): void
    {
        $this->aiOn();
        $c = $this->webLogin();
        Http::fake(['*chat/completions*' => Http::response($this->aiJson())]);
        $this->get('/audit')->assertOk()->assertSee('Marketplace Audit');
        $p = $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/Kebaya-i.11.22'])->assertOk()->assertJsonPath('status', 'waiting')->json();
        $this->assertSame(10, Audit::quota($c)['left']);   // belum terhitung

        // extension mengirim data
        $this->postJson("/api/audit/scans/{$p['id']}/data", $this->productPayload(), ['X-Audit-Token' => 'salah'])->assertStatus(403);
        $this->postJson("/api/audit/scans/{$p['id']}/data", $this->productPayload(), ['X-Audit-Token' => $p['token']])->assertOk()->assertJson(['status' => 'collected']);
        $this->postJson("/api/audit/scans/{$p['id']}/data", $this->productPayload(), ['X-Audit-Token' => $p['token']])->assertStatus(409);   // sekali pakai

        $r = $this->postJson("/audit/scans/{$p['id']}/analyze")->assertOk()->assertJsonPath('status', 'done')->json();
        $this->assertGreaterThan(60, $r['result']['score']);
        $this->assertSame('Judul 1', $r['result']['ai']['saran_judul'][0]);
        $this->assertSame(9, Audit::quota($c)['left']);
        $this->assertArrayNotHasKey('junk', AuditScan::find($p['id'])->data['product'] ?? []);
        $this->getJson("/audit/scans/{$p['id']}")->assertJsonPath('result.kind', 'product');
    }

    public function test_ai_failure_does_not_consume_quota_and_can_retry(): void
    {
        $this->aiOn();
        $c = $this->webLogin();
        Http::fake(['*chat/completions*' => Http::response(['error' => 'x'], 500)]);
        $p = $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/Kebaya-i.11.22'])->json();
        $this->postJson("/api/audit/scans/{$p['id']}/data", $this->productPayload(), ['X-Audit-Token' => $p['token']])->assertOk();
        $this->postJson("/audit/scans/{$p['id']}/analyze")->assertOk()->assertJsonPath('status', 'failed');
        $this->assertSame(10, Audit::quota($c)['left']);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*chat/completions*' => Http::response($this->aiJson())]);
        $this->postJson("/audit/scans/{$p['id']}/analyze")->assertOk()->assertJsonPath('status', 'done');
        $this->assertSame(9, Audit::quota($c)['left']);
    }

    public function test_quota_blocks_after_limit_and_subscription_unlocks(): void
    {
        $c = $this->webLogin();
        Settings::set('audit.free_scans', '2');
        for ($i = 0; $i < 2; $i++) AuditScan::query()->create(['customer_id' => $c->id, 'token_hash' => 'h', 'kind' => 'shop', 'status' => 'done', 'counted' => true]);
        $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/toko'])->assertStatus(402);
        $s = $c->subscription('audit', true);
        $s->update(['paid_until' => now()->addDays(30), 'paid_plan' => 'monthly']);
        $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/toko'])->assertOk();
        $this->assertTrue(Audit::quota($c->fresh())['unlimited']);
    }

    public function test_paid_daily_cap(): void
    {
        $c = $this->webLogin();
        Settings::set('audit.paid_daily_cap', '1');
        $c->subscription('audit', true)->update(['paid_until' => now()->addDays(30), 'paid_plan' => 'monthly']);
        AuditScan::query()->create(['customer_id' => $c->id, 'token_hash' => 'h', 'kind' => 'shop', 'status' => 'done', 'counted' => true]);
        $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/toko'])->assertStatus(402);
    }

    public function test_one_active_scan_and_cancel(): void
    {
        $this->webLogin();
        $p = $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/toko'])->assertOk()->json();
        $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/toko2'])->assertStatus(429);
        $this->postJson("/audit/scans/{$p['id']}/cancel")->assertJsonPath('status', 'cancelled');
        $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/toko2'])->assertOk();
    }

    public function test_needs_login_then_rearm(): void
    {
        $this->webLogin();
        $p = $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/toko'])->json();
        $this->postJson("/api/audit/scans/{$p['id']}/data", ['status' => 'needs_login'], ['X-Audit-Token' => $p['token']])->assertOk()->assertJson(['status' => 'needs_login']);
        $n = $this->postJson("/audit/scans/{$p['id']}/rearm")->assertOk()->json();
        $this->assertNotSame($p['token'], $n['token']);
        $this->postJson("/api/audit/scans/{$p['id']}/data", ['status' => 'needs_login'], ['X-Audit-Token' => $p['token']])->assertStatus(403);   // token lama mati
        $this->postJson("/api/audit/scans/{$p['id']}/data", ['status' => 'ok', 'shop' => ['name' => 'Toko A', 'rating' => 4.9, 'followers' => 5000, 'response_rate' => 98, 'product_count' => 40], 'products' => [['name' => 'Produk A', 'sold' => 3]]], ['X-Audit-Token' => $n['token']])
            ->assertOk()->assertJson(['status' => 'collected']);
    }

    public function test_cannot_read_other_users_scan(): void
    {
        $this->webLogin('a@x.test');
        $p = $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/toko'])->json();
        $this->post('/keluar');
        $this->webLogin('b@x.test');
        $this->getJson("/audit/scans/{$p['id']}")->assertNotFound();
        $this->postJson("/audit/scans/{$p['id']}/analyze")->assertNotFound();
    }

    public function test_rules_for_shop(): void
    {
        $d = Audit::normalizeShop(['shop' => ['name' => 'Toko', 'rating' => 4.9, 'response_rate' => 60, 'followers' => 20, 'product_count' => 3], 'products' => [['name' => 'A', 'sold' => 0], ['name' => 'B', 'sold' => 0]]]);
        $r = Audit::rules('shop', $d);
        $st = collect($r['checks'])->pluck('status', 'title');
        $this->assertSame('ok', $st['Rating toko sangat baik']);
        $this->assertSame('bad', $st['Balasan chat rendah']);
        $this->assertLessThan(70, $r['score']);
        $this->assertNull(Audit::normalizeShop(['shop' => ['rating' => 5]]));
    }

    public function test_without_ai_still_gives_rule_based_result(): void
    {
        $c = $this->webLogin();
        $p = $this->postJson('/audit/scans', ['url' => 'https://shopee.co.id/Kebaya-i.11.22'])->json();
        $this->postJson("/api/audit/scans/{$p['id']}/data", $this->productPayload(), ['X-Audit-Token' => $p['token']])->assertOk();
        $r = $this->postJson("/audit/scans/{$p['id']}/analyze")->assertOk()->assertJsonPath('status', 'done')->json();
        $this->assertNull($r['result']['ai']);
        $this->assertSame(9, Audit::quota($c)['left']);
    }

    public function test_purge_keeps_counter_only(): void
    {
        $c = $this->webLogin();
        $s = AuditScan::query()->create(['customer_id' => $c->id, 'token_hash' => 'h', 'kind' => 'shop', 'url' => 'https://shopee.co.id/toko', 'status' => 'done', 'counted' => true, 'data' => ['a' => 1], 'result' => ['score' => 5]]);
        AuditScan::query()->whereKey($s->id)->update(['updated_at' => now()->subHours(30)]);
        Audit::purge();
        $s->refresh();
        $this->assertNull($s->url); $this->assertNull($s->data); $this->assertNull($s->result); $this->assertTrue($s->counted); $this->assertNotNull($s->purged_at);
        $this->assertSame(9, Audit::quota($c)['left']);
    }

    public function test_closed_and_pages(): void
    {
        $this->get('/marketplace-audit')->assertOk()->assertSee('tidak menyimpan apa pun');
        $this->get('/')->assertOk()->assertSee('Marketplace Audit');
        Settings::set('audit.enabled', '0');
        $this->get('/marketplace-audit')->assertNotFound();
    }

    public function test_admin_page(): void
    {
        $this->webLogin();
        $this->post('/keluar');
        $this->actingAs(User::query()->create(['name' => 'Adm', 'username' => 'adm', 'email' => 'adm@test.id', 'password' => 'rahasia123']));
        $this->get('/admin/audit')->assertOk()->assertSee('Marketplace Audit')->assertSee('Atur jatah');
    }
}
