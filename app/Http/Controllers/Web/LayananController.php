<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ServicePlan;
use App\Models\ServicePortfolio;
use App\Models\ServiceSubscription;
use App\Models\ServiceType;
use App\Services\Layanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Layanan kelola Instagram & Threads: halaman publik, pesan paket, dan status langganan layanan di akun pelanggan. */
class LayananController extends Controller
{
    public function index()
    {
        abort_unless(Layanan::enabled(), 404);
        $types = ServiceType::query()->where('is_active', true)->orderBy('sort')->orderBy('id')
            ->with(['plans' => fn ($q) => $q->where('is_active', true)->where('price', '>', 0)])->get()->filter(fn ($t) => $t->plans->isNotEmpty())->values();
        return view('public.layanan', [
            'types' => $types, 'portfolio' => ServicePortfolio::query()->where('is_active', true)->orderBy('sort')->orderByDesc('id')->get(),
            'wa' => Layanan::waUrl(), 'hubUrl' => url('/'),
        ]);
    }

    /** Daftar semua layanan: kelola sosmed (aktif) + layanan yang segera hadir. */
    public function overview()
    {
        abort_unless(Layanan::enabled(), 404);
        return view('public.layanan-index', ['soon' => Layanan::comingSoonAll(), 'wa' => Layanan::waUrl(), 'hubUrl' => url('/')]);
    }

    /** Gambar portofolio dari storage (tanpa storage:link). Versi di URL hanya untuk cache-busting. */
    public function image(int $id, string $v)
    {
        $p = ServicePortfolio::query()->where('is_active', true)->find($id);
        abort_unless($p && $p->image_path && Storage::disk('local')->exists($p->image_path), 404);
        return Storage::disk('local')->response($p->image_path, null, ['Cache-Control' => 'public, max-age=86400']);
    }

    /** Tombol "Pesan" di halaman publik: simpan pilihan paket, lanjut login bila perlu. */
    public function start(Request $r, int $plan)
    {
        abort_unless(Layanan::enabled(), 404);
        if (!ServicePlan::orderable()->whereKey($plan)->exists()) return redirect()->route('layanan.sosmed')->with('error', 'Paket tidak tersedia.');
        $r->session()->put('layanan_plan', $plan);
        return $r->session()->has('customer_id') ? redirect()->route('portal.layanan') : redirect()->route('portal.login');
    }

    public function account(Request $r)
    {
        $c = $r->attributes->get('customer');
        $pending = ($id = $r->session()->get('layanan_plan')) ? ServicePlan::orderable()->find($id) : null;
        if (!$pending) $r->session()->forget('layanan_plan');
        return view('portal.layanan', [
            'c' => $c, 'subs' => $c->serviceSubscriptions()->get(), 'pending' => Layanan::enabled() ? $pending : null,
            'wa' => Layanan::waNumber() !== '' ? true : false, 'payment' => Layanan::paymentInfo(), 'enabled' => Layanan::enabled(),
        ]);
    }

    /** Konfirmasi pesanan -> langganan berstatus "menunggu konfirmasi"; admin mengaktifkan setelah pembayaran. */
    public function order(Request $r)
    {
        abort_unless(Layanan::enabled(), 404);
        $c = $r->attributes->get('customer');
        $plan = ServicePlan::orderable()->find((int) $r->input('plan'));
        if (!$plan) return redirect()->route('portal.layanan')->with('error', 'Paket tidak tersedia.');
        $d = $r->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $r->session()->forget('layanan_plan');
        $open = $c->serviceSubscriptions()->where('service_plan_id', $plan->id)->where('status', 'pending')->first();
        if ($open) return redirect()->route('portal.layanan')->with('ok', 'Pesanan paket ini sudah tercatat dan menunggu konfirmasi.');
        ServiceSubscription::query()->create(['customer_id' => $c->id, 'service_plan_id' => $plan->id, 'plan_name' => $plan->type->name . ' – ' . $plan->name,
            'price' => $plan->price, 'status' => 'pending', 'customer_note' => $d['note'] ?? null]);
        return redirect()->route('portal.layanan')->with('ok', 'Pesanan tercatat. Lanjutkan pembayaran & konfirmasi lewat WhatsApp; admin akan mengaktifkan paket Anda.');
    }

    /** Pelanggan memperbarui catatan (nama akun/brand, kebutuhan) pada langganannya. */
    public function note(Request $r, int $sub)
    {
        $c = $r->attributes->get('customer');
        $s = $c->serviceSubscriptions()->whereKey($sub)->firstOrFail();
        $d = $r->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $s->update(['customer_note' => $d['note'] ?? null]);
        return redirect()->route('portal.layanan')->with('ok', 'Catatan disimpan.');
    }
}
