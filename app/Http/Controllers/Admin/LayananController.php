<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Customer;
use App\Models\ServicePlan;
use App\Models\ServicePortfolio;
use App\Models\ServiceSubscription;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Admin layanan Kelola Instagram & Threads: jenis & paket (dinamis), portofolio, langganan pelanggan. */
class LayananController extends Controller
{
    // ---------- jenis paket ----------
    public function plans()
    {
        return view('admin.layanan.plans', ['types' => ServiceType::query()->orderBy('sort')->orderBy('id')->with('plans')->get()]);
    }

    public function typeForm(?ServiceType $type = null)
    {
        return view('admin.layanan.type-form', ['type' => $type ?? new ServiceType(['is_active' => true, 'sort' => (int) ServiceType::query()->max('sort') + 1])]);
    }

    public function typeSave(Request $r, ?ServiceType $type = null)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:80'], 'description' => ['nullable', 'string', 'max:255'], 'sort' => ['nullable', 'integer', 'min:0', 'max:999']]);
        $d['sort'] = (int) ($d['sort'] ?? 0);
        $d['is_active'] = $r->boolean('is_active');
        if ($type) { $type->update($d); AdminLog::record('layanan_type', 'Jenis paket diubah: ' . $type->name); }
        else {
            $slug = Str::slug($d['name']) ?: 'jenis'; $base = $slug; $i = 2;
            while (ServiceType::query()->where('slug', $slug)->exists()) $slug = $base . '-' . $i++;
            $type = ServiceType::query()->create($d + ['slug' => $slug]);
            AdminLog::record('layanan_type', 'Jenis paket baru: ' . $type->name);
        }
        return redirect()->route('admin.layanan.plans')->with('ok', 'Jenis paket disimpan.');
    }

    public function typeDelete(ServiceType $type)
    {
        if ($type->plans()->exists()) return back()->with('error', 'Jenis ini masih punya paket. Hapus atau pindahkan paketnya dulu, atau cukup nonaktifkan.');
        AdminLog::record('layanan_type', 'Jenis paket dihapus: ' . $type->name);
        $type->delete();
        return back()->with('ok', 'Jenis paket dihapus.');
    }

    // ---------- paket ----------
    public function planForm(?ServicePlan $plan = null)
    {
        $types = ServiceType::query()->orderBy('sort')->get();
        if ($types->isEmpty()) return redirect()->route('admin.layanan.plans')->with('error', 'Buat jenis paket dulu.');
        return view('admin.layanan.plan-form', ['plan' => $plan ?? new ServicePlan(['is_active' => false, 'price' => 0, 'period' => '/bulan', 'duration_days' => 30, 'sort' => (int) ServicePlan::query()->max('sort') + 1,
            'service_type_id' => request('jenis')]), 'types' => $types]);
    }

    public function planSave(Request $r, ?ServicePlan $plan = null)
    {
        $d = $r->validate([
            'service_type_id' => ['required', 'exists:service_types,id'], 'name' => ['required', 'string', 'max:80'], 'tagline' => ['nullable', 'string', 'max:160'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000000'], 'period' => ['nullable', 'string', 'max:30'], 'features' => ['nullable', 'string', 'max:2000'],
            'badge' => ['nullable', 'string', 'max:30'], 'duration_days' => ['required', 'integer', 'min:1', 'max:3650'], 'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
        $d['sort'] = (int) ($d['sort'] ?? 0);
        $d['period'] = $d['period'] ?? '';
        $d['is_featured'] = $r->boolean('is_featured');
        $d['is_active'] = $r->boolean('is_active');
        if ($d['is_active'] && (int) $d['price'] <= 0) return back()->withInput()->with('error', 'Paket aktif harus punya harga lebih dari 0. Isi harga dulu atau biarkan nonaktif.');
        if ($plan) { $plan->update($d); AdminLog::record('layanan_plan', 'Paket diubah: ' . $plan->name); }
        else { $plan = ServicePlan::query()->create($d); AdminLog::record('layanan_plan', 'Paket baru: ' . $plan->name); }
        return redirect()->route('admin.layanan.plans')->with('ok', 'Paket disimpan.');
    }

    public function planDelete(ServicePlan $plan)
    {
        AdminLog::record('layanan_plan', 'Paket dihapus: ' . $plan->name);
        $plan->delete();   // riwayat langganan tetap menyimpan nama & harga saat memesan
        return back()->with('ok', 'Paket dihapus.');
    }

    // ---------- portofolio ----------
    public function portfolio()
    {
        return view('admin.layanan.portfolio', ['items' => ServicePortfolio::query()->orderBy('sort')->orderByDesc('id')->get()]);
    }

    public function portfolioForm(?ServicePortfolio $item = null)
    {
        return view('admin.layanan.portfolio-form', ['item' => $item ?? new ServicePortfolio(['is_active' => true, 'sort' => (int) ServicePortfolio::query()->max('sort') + 1])]);
    }

    public function portfolioSave(Request $r, ?ServicePortfolio $item = null)
    {
        $d = $r->validate([
            'title' => ['required', 'string', 'max:120'], 'client_name' => ['nullable', 'string', 'max:120'], 'platform' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:1000'], 'result_text' => ['nullable', 'string', 'max:160'],
            'link_url' => ['nullable', 'string', 'max:255', 'regex:#^https://[^\s]+$#i'], 'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], ['link_url.regex' => 'Tautan harus diawali https://', 'image.max' => 'Gambar maksimal 3 MB.']);
        unset($d['image']);
        $d['sort'] = (int) ($d['sort'] ?? 0);
        $d['is_active'] = $r->boolean('is_active');
        $new = !$item;
        $item = $item ?? new ServicePortfolio();
        $item->fill($d);
        if ($r->hasFile('image')) {
            $item->deleteImage();
            $item->image_path = $r->file('image')->store('layanan', 'local');
        } elseif ($r->boolean('remove_image')) {
            $item->deleteImage();
            $item->image_path = null;
        }
        $item->save();
        AdminLog::record('layanan_portfolio', ($new ? 'Portofolio baru: ' : 'Portofolio diubah: ') . $item->title);
        return redirect()->route('admin.layanan.portfolio')->with('ok', 'Portofolio disimpan.');
    }

    public function portfolioDelete(ServicePortfolio $item)
    {
        AdminLog::record('layanan_portfolio', 'Portofolio dihapus: ' . $item->title);
        $item->deleteImage();
        $item->delete();
        return back()->with('ok', 'Portofolio dihapus.');
    }

    // ---------- langganan pelanggan ----------
    public function subs(Request $r)
    {
        $q = ServiceSubscription::query()->with('customer')->latest('id');
        if (in_array($r->query('status'), array_keys(ServiceSubscription::STATUSES), true)) $q->where('status', $r->query('status'));
        return view('admin.layanan.subs', ['subs' => $q->paginate(30)->withQueryString(), 'status' => $r->query('status')]);
    }

    public function subForm(?ServiceSubscription $sub = null)
    {
        return view('admin.layanan.sub-form', ['sub' => $sub ?? new ServiceSubscription(['status' => 'active', 'starts_at' => now()]), 'plans' => ServicePlan::query()->with('type')->orderBy('sort')->get()]);
    }

    public function subSave(Request $r, ?ServiceSubscription $sub = null)
    {
        $d = $r->validate([
            'email' => $sub ? ['nullable'] : ['required', 'email'],
            'service_plan_id' => ['nullable', 'exists:service_plans,id'], 'plan_name' => ['nullable', 'string', 'max:120'], 'price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:' . implode(',', array_keys(ServiceSubscription::STATUSES))],
            'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'drive_url' => ['nullable', 'string', 'max:500', 'regex:#^https://[^\s]+$#i'], 'admin_note' => ['nullable', 'string', 'max:1000'],
        ], ['drive_url.regex' => 'URL Drive harus diawali https://', 'ends_at.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.']);
        $plan = !empty($d['service_plan_id']) ? ServicePlan::query()->find($d['service_plan_id']) : null;
        if (!$sub) {
            $c = Customer::query()->where('email', $d['email'])->first();
            if (!$c) return back()->withInput()->with('error', 'Pelanggan dengan email itu belum pernah masuk ke temanseller.com.');
            $sub = new ServiceSubscription(['customer_id' => $c->id]);
        }
        $sub->fill([
            'service_plan_id' => $plan?->id ?? $sub->service_plan_id,
            'plan_name' => trim((string) ($d['plan_name'] ?? '')) ?: ($plan?->name ?? $sub->plan_name ?: 'Paket kustom'),
            'price' => isset($d['price']) && $d['price'] !== null ? (int) $d['price'] : ($plan?->price ?? $sub->price ?? 0),
            'status' => $d['status'], 'starts_at' => $d['starts_at'] ?? null, 'ends_at' => $d['ends_at'] ?? null,
            'drive_url' => $d['drive_url'] ?? null, 'admin_note' => $d['admin_note'] ?? null,
        ]);
        // aktivasi tanpa tanggal: pakai lama paket (bawaan 30 hari)
        if ($sub->status === 'active') {
            $sub->starts_at ??= now();
            $sub->ends_at ??= $sub->starts_at->copy()->addDays($plan?->duration_days ?? 30);
        }
        $sub->save();
        AdminLog::record('layanan_sub', "Langganan layanan #{$sub->id} ({$sub->status}) pelanggan #{$sub->customer_id}");
        return redirect()->route('admin.layanan.subs')->with('ok', 'Langganan layanan disimpan.');
    }
}
