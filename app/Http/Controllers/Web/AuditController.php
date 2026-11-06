<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\App as AppModel;
use App\Models\AuditScan;
use App\Services\Audit;
use App\Services\Plans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Marketplace Audit: halaman publik, halaman audit (wajib login Google), dan API sesi web untuk memulai/memantau pemindaian. */
class AuditController extends Controller
{
    public function landing()
    {
        abort_unless(Audit::enabled(), 404);
        return view('public.audit', [
            'free' => Audit::freeScans(), 'plans' => Plans::all(Audit::APP), 'hubUrl' => url('/'),
            'loggedIn' => request()->session()->has('customer_id'),
        ]);
    }

    public function page(Request $r)
    {
        $c = $r->attributes->get('customer');
        Audit::expireStale($c);
        $recent = AuditScan::query()->where('customer_id', $c->id)->whereNull('purged_at')->where('status', 'done')->latest('id')->limit(10)->get(['id', 'kind', 'url', 'result', 'created_at'])
            ->map(fn ($s) => ['id' => $s->id, 'kind' => $s->kind, 'name' => $s->result['subject']['name'] ?? $s->url, 'score' => $s->result['score'] ?? null, 'at' => $s->created_at->timezone(config('temanseller.display_timezone'))->format('d M H:i')]);
        return view('portal.audit', [
            'c' => $c, 'q' => Audit::quota($c), 'recent' => $recent, 'plans' => Plans::all(Audit::APP),
            'hours' => Audit::retentionHours(), 'enabled' => Audit::enabled(), 'apiBase' => rtrim(url('/api'), '/'),
        ]);
    }

    private function own(Request $r, int $id): AuditScan
    {
        $s = AuditScan::query()->find($id);
        abort_unless($s && $s->customer_id === $r->attributes->get('customer')->id, 404);
        return $s;
    }

    private function view(AuditScan $s, array $extra = []): array
    {
        return ['id' => $s->id, 'kind' => $s->kind, 'status' => $s->status, 'error' => $s->error, 'result' => $s->status === 'done' ? $s->result : null] + $extra;
    }

    public function create(Request $r): JsonResponse
    {
        $c = $r->attributes->get('customer');
        $r->validate(['url' => 'required|string|max:500']);
        $x = Audit::create($c, (string) $r->input('url'));
        return response()->json($this->view($x['scan'], ['token' => $x['token'], 'target' => $x['target'], 'api' => rtrim(url('/api'), '/'), 'quota' => Audit::quota($c)]));
    }

    /** Unggah PDF halaman web / screenshot: dibaca di memori lalu dibuang, tidak ada berkas yang disimpan. */
    public function upload(Request $r): JsonResponse
    {
        $c = $r->attributes->get('customer');
        $v = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'kind' => 'required|in:product,shop',
            'url' => 'nullable|string|max:500',
            'pdfs' => 'nullable|array|max:' . Audit::MAX_PAGES,
            'pdfs.*' => 'file|extensions:pdf|max:10240',
            'shots' => 'nullable|array|max:' . Audit::MAX_IMAGES,
            'shots.*' => 'file|extensions:jpg,jpeg,png,webp|max:6144',
        ], [
            'pdfs.*.extensions' => 'Halaman web harus diunggah sebagai file PDF.',
            'shots.*.extensions' => 'Screenshot harus JPG, PNG, atau WEBP.',
            'pdfs.*.max' => 'Setiap PDF maksimal 10 MB.', 'shots.*.max' => 'Setiap screenshot maksimal 6 MB.',
        ]);
        if ($v->fails()) throw new ApiException((string) $v->errors()->first(), 422);
        $pages = []; $images = [];
        foreach ((array) $r->file('pdfs', []) as $f) {
            $txt = Audit::pdfText((string) file_get_contents($f->getRealPath()));   // hanya dibaca; berkas sementara PHP hilang di akhir request
            if (mb_strlen($txt) < 200) throw new ApiException('Teks di PDF hampir kosong. Pastikan halaman termuat penuh sebelum dicetak ke PDF, atau pakai screenshot.', 422);
            $pages[] = $txt;
        }
        foreach ((array) $r->file('shots', []) as $f) $images[] = Audit::prepareImage((string) file_get_contents($f->getRealPath()));
        $scan = Audit::createUpload($c, (string) $r->input('kind'), $r->input('url'), $pages, $images);
        return response()->json($this->view($scan, ['quota' => Audit::quota($c)]));
    }

    public function show(Request $r, int $id): JsonResponse
    {
        $s = $this->own($r, $id);
        if ($s->isActive()) Audit::expireStale($r->attributes->get('customer'));
        return response()->json($this->view($s->refresh(), ['quota' => Audit::quota($r->attributes->get('customer'))]));
    }

    public function analyze(Request $r, int $id): JsonResponse
    {
        $s = $this->own($r, $id);
        if (!in_array($s->status, ['collected', 'failed'], true) || $s->data === null) throw new ApiException('Belum ada data untuk dianalisis.', 409);
        $s = Audit::analyze($s);
        return response()->json($this->view($s, ['quota' => Audit::quota($r->attributes->get('customer'))]));
    }

    /** Setelah pengguna login Shopee: minta token baru lalu kirim ulang ke extension. */
    public function rearm(Request $r, int $id): JsonResponse
    {
        $s = $this->own($r, $id);
        if ($s->status !== 'needs_login') throw new ApiException('Pemindaian tidak sedang menunggu login Shopee.', 409);
        $token = Audit::rearm($s);
        return response()->json($this->view($s->refresh(), ['token' => $token, 'target' => Audit::parseUrl((string) $s->url), 'api' => rtrim(url('/api'), '/')]));
    }

    public function cancel(Request $r, int $id): JsonResponse
    {
        $s = $this->own($r, $id);
        if ($s->isActive() || in_array($s->status, ['needs_login', 'failed'], true)) $s->update(['status' => 'cancelled', 'data' => null, 'error' => null]);
        return response()->json($this->view($s->refresh()));
    }

    public function destroy(Request $r, int $id): JsonResponse
    {
        $s = $this->own($r, $id);
        if (!$s->isActive()) $s->update(['url' => null, 'data' => null, 'result' => null, 'purged_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
