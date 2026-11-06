@extends('layouts.portal', ['title' => 'Marketplace Audit'])
@use('App\Support\Fmt')
@section('nav')
<a href="{{ route('portal.account') }}" class="mr-4 text-sm text-gray-500 hover:text-gray-800">← Akun saya</a>
<form method="POST" action="{{ route('portal.logout') }}" class="inline">@csrf<button class="text-sm text-gray-500 hover:text-gray-800">Keluar</button></form>
@endsection
@section('content')
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-xl font-semibold">🩺 Marketplace Audit</h1>
        <p class="text-sm text-gray-500">Audit toko atau produk Shopee, dibantu AI. Tempel tautannya, kami periksa.</p>
    </div>
    <div id="quota" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm">
        @if ($q['unlimited'])
            <span class="font-semibold text-success-700">Unlimited</span> <span class="text-gray-500">sampai {{ \Illuminate\Support\Carbon::createFromTimestamp($q['until'])->timezone(config('temanseller.display_timezone'))->format('d M Y') }}</span>
        @else
            Sisa jatah bulan ini: <b id="left">{{ $q['left'] }}</b> / {{ $q['limit'] }} scan
        @endif
    </div>
</div>

@unless ($enabled)
    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500">Marketplace Audit sedang ditutup sementara.</div>
@else
<section class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
    <label class="block text-sm font-medium text-gray-700" for="url">Tautan toko atau produk Shopee</label>
    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
        <input id="url" type="url" inputmode="url" placeholder="https://shopee.co.id/namatoko  atau  https://shopee.co.id/nama-produk-i.123.456" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-4 py-3 text-sm">
        <button id="go" class="rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">Mulai audit</button>
    </div>
    <p id="formerr" class="mt-3 hidden text-sm text-error-600"></p>
    @if (!$q['can'])
        <div class="mt-4 rounded-lg bg-warning-50 p-4 text-sm text-warning-700">{{ $q['reason'] }}
            @if (!$q['unlimited'] && collect($plans)->contains(fn ($p) => ($p['price'] ?? 0) > 0))<a class="font-semibold underline" href="{{ route('portal.app', 'audit') }}">Lihat paket unlimited</a>@endif
        </div>
    @endif
    <div class="mt-5 rounded-lg bg-gray-50 p-4 text-xs leading-relaxed text-gray-600">
        <b class="text-gray-800">Privasi:</b> aplikasi ini tidak menyimpan apa pun dari akun Shopee-mu — tidak membaca atau menyimpan kata sandi, cookie, maupun sesi login. Pengumpulan data berjalan di browsermu sendiri lewat extension. Server hanya menerima data publik toko/produk yang kamu minta diaudit, lalu menghapusnya otomatis (tautan, data, dan hasil) setelah <b>{{ $hours }} jam</b>; yang tersisa hanya hitungan jatah scan.
    </div>
</section>

<section id="ext" class="mt-6 hidden rounded-2xl border-2 border-warning-300 bg-white p-6">
    <div class="font-semibold">Pasang extension dulu</div>
    <p class="mt-1 text-sm text-gray-600">Audit memakai browser Chrome-mu untuk membaca halaman Shopee (kamu harus sedang login di Shopee). Pasang extension TemanSeller, lalu muat ulang halaman ini.</p>
    <div class="mt-3 flex gap-3">
        @if ($ext)<a href="{{ $ext }}" target="_blank" rel="noopener" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Pasang extension ↗</a>@else<span class="text-sm text-gray-500">Tautan pemasangan belum diisi admin.</span>@endif
        <button onclick="location.reload()" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700">Sudah dipasang, muat ulang</button>
    </div>
</section>

<section id="login" class="mt-6 hidden rounded-2xl border-2 border-warning-300 bg-white p-6">
    <div class="font-semibold">Masuk ke Shopee dulu</div>
    <p class="mt-1 text-sm text-gray-600">Kamu belum login di Shopee pada browser ini. Buka Shopee, login seperti biasa (kami tidak melihat atau menyimpan data login-mu), lalu kembali ke sini dan lanjutkan.</p>
    <div class="mt-3 flex flex-wrap gap-3">
        <a href="https://shopee.co.id/buyer/login" target="_blank" rel="noopener" class="rounded-lg bg-ts-orange px-5 py-2.5 text-sm font-medium text-ts-navy">Buka Shopee untuk login ↗</a>
        <button id="resume" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700">Saya sudah login, lanjutkan</button>
        <button class="cancel rounded-lg px-3 py-2.5 text-sm text-gray-500">Batalkan</button>
    </div>
</section>

<section id="progress" class="mt-6 hidden rounded-2xl border border-gray-200 bg-white p-6">
    <div class="flex items-center gap-3"><span class="size-3 animate-pulse rounded-full bg-brand-500"></span><div><div id="pstage" class="font-medium">Menunggu…</div><div id="phint" class="text-sm text-gray-500"></div></div></div>
    <button class="cancel mt-4 text-sm text-gray-500 underline">Batalkan</button>
</section>

<section id="fail" class="mt-6 hidden rounded-2xl border border-error-300 bg-error-50 p-6 text-sm text-error-700">
    <div id="failmsg" class="font-medium"></div>
    <button id="retry" class="mt-3 hidden rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-800 ring-1 ring-gray-300">Coba lagi analisis</button>
</section>

<section id="result" class="mt-6 hidden"></section>

@if ($recent->isNotEmpty())
<section class="mt-8">
    <h2 class="text-sm font-semibold text-gray-700">Riwayat ({{ $hours }} jam terakhir)</h2>
    <div class="mt-2 divide-y divide-gray-100 rounded-2xl border border-gray-200 bg-white">
        @foreach ($recent as $r)
            <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm" data-hist="{{ $r['id'] }}">
                <div class="min-w-0"><div class="truncate font-medium">{{ $r['name'] }}</div><div class="text-xs text-gray-500">{{ $r['kind'] === 'product' ? 'Produk' : 'Toko' }} · {{ $r['at'] }} · skor {{ $r['score'] }}</div></div>
                <div class="flex shrink-0 gap-3"><button class="text-brand-500" data-open="{{ $r['id'] }}">Buka</button><button class="text-gray-400 hover:text-error-600" data-del="{{ $r['id'] }}">Hapus</button></div>
            </div>
        @endforeach
    </div>
</section>
@endif
@endunless
@endsection

@push('scripts')
<script>
(() => {
const $ = (id) => document.getElementById(id);
const csrf = document.querySelector('meta[name=csrf-token]').content;
const EXT_VER_MIN = 1;
let scan = null, ext = false, timer = null, analyzing = false;
const show = (...ids) => ['ext', 'login', 'progress', 'fail', 'result'].forEach((i) => $(i) && $(i).classList.toggle('hidden', !ids.includes(i)));
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
async function api(method, path, body) {
    const r = await fetch(path, { method, headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: body ? JSON.stringify(body) : undefined });
    const j = await r.json().catch(() => ({}));
    if (!r.ok) throw Object.assign(new Error(j.error || j.message || 'Terjadi kesalahan (' + r.status + ')'), { status: r.status });
    return j;
}
// --- jembatan ke extension (content script di halaman ini) ---
function ping() {
    return new Promise((res) => {
        const t = setTimeout(() => { off(); res(false); }, 1500);
        const h = (e) => { if (e.source === window && e.data && e.data.source === 'ts-audit-ext' && e.data.type === 'pong') { clearTimeout(t); off(); res((e.data.v || 0) >= EXT_VER_MIN); } };
        const off = () => window.removeEventListener('message', h);
        window.addEventListener('message', h);
        window.postMessage({ source: 'ts-audit-page', type: 'ping' }, location.origin);
    });
}
const startExt = (p) => window.postMessage({ source: 'ts-audit-page', type: 'start', scanId: p.id, token: p.token, api: p.api, target: p.target }, location.origin);
const cancelExt = () => window.postMessage({ source: 'ts-audit-page', type: 'cancel' }, location.origin);

function setQuota(q) { if (!q) return; const el = $('left'); if (el && q.left !== null) el.textContent = q.left; }
function ferr(m) { const e = $('formerr'); e.textContent = m; e.classList.toggle('hidden', !m); }

const STAGES = { waiting: ['Mengumpulkan data dari Shopee…', 'Extension membuka halaman Shopee di latar belakang. Jangan tutup tab ini.'], collected: ['Data terkumpul', 'Menyiapkan analisis…'], analyzing: ['AI sedang menganalisis…', 'Biasanya 10–40 detik.'] };
function poll() {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        if (!scan) return;
        try {
            const s = await api('GET', '{{ url('/audit/scans') }}/' + scan.id);
            setQuota(s.quota);
            handle(s);
        } catch (e) { if (e.status === 404) { reset(); } else poll(); }
    }, 2000);
}
function handle(s) {
    if (s.status === 'waiting' || s.status === 'analyzing') { stage(s.status); return poll(); }
    if (s.status === 'collected') { stage('collected'); if (!analyzing) runAnalyze(); return poll(); }
    if (s.status === 'needs_login') { show('login'); return; }
    if (s.status === 'done') { scan = null; renderResult(s.result); return; }
    if (s.status === 'failed' && s.error && /AI gagal/.test(s.error)) { $('failmsg').textContent = s.error; $('retry').classList.remove('hidden'); show('fail'); return; }
    if (['failed', 'expired', 'cancelled'].includes(s.status)) {
        $('failmsg').textContent = s.status === 'cancelled' ? 'Pemindaian dibatalkan.' : (s.error || 'Pemindaian gagal.'); $('retry').classList.add('hidden'); show('fail'); scan = null; $('go').disabled = false;
    }
}
function stage(k) { show('progress'); $('pstage').textContent = STAGES[k][0]; $('phint').textContent = STAGES[k][1]; }
async function runAnalyze() {
    analyzing = true;
    try { const s = await api('POST', '{{ url('/audit/scans') }}/' + scan.id + '/analyze'); setQuota(s.quota); handle(s); }
    catch (e) { $('failmsg').textContent = e.message; $('retry').classList.add('hidden'); show('fail'); }
    finally { analyzing = false; $('go').disabled = false; }
}
function reset() { scan = null; clearTimeout(timer); $('go').disabled = false; show(); }

$('go')?.addEventListener('click', async () => {
    ferr('');
    const url = $('url').value.trim();
    if (!url) return ferr('Tempel tautan toko atau produk Shopee dulu.');
    $('go').disabled = true;
    ext = await ping();
    if (!ext) { show('ext'); $('go').disabled = false; return; }
    try {
        const p = await api('POST', '{{ route('audit.create') }}', { url });
        scan = p; setQuota(p.quota); stage('waiting'); startExt(p); poll();
    } catch (e) { ferr(e.message); $('go').disabled = false; }
});
$('resume')?.addEventListener('click', async () => {
    try { const p = await api('POST', '{{ url('/audit/scans') }}/' + scan.id + '/rearm'); scan.token = p.token; stage('waiting'); startExt({ id: scan.id, token: p.token, api: p.api, target: p.target }); poll(); }
    catch (e) { $('failmsg').textContent = e.message; show('fail'); }
});
$('retry')?.addEventListener('click', async () => { $('fail').classList.add('hidden'); stage('analyzing'); try { await runAnalyze(); } catch (_) {} });
document.querySelectorAll('.cancel').forEach((b) => b.addEventListener('click', async () => { cancelExt(); if (scan) { try { await api('POST', '{{ url('/audit/scans') }}/' + scan.id + '/cancel'); } catch (_) {} } reset(); }));
document.querySelectorAll('[data-open]').forEach((b) => b.addEventListener('click', async () => { try { const s = await api('GET', '{{ url('/audit/scans') }}/' + b.dataset.open); if (s.result) renderResult(s.result); } catch (e) { ferr(e.message); } }));
document.querySelectorAll('[data-del]').forEach((b) => b.addEventListener('click', async () => { if (!confirm('Hapus hasil ini sekarang?')) return; try { await api('DELETE', '{{ url('/audit/scans') }}/' + b.dataset.del); b.closest('[data-hist]').remove(); } catch (e) { ferr(e.message); } }));

// --- tampilan hasil (semua teks di-escape) ---
const ICON = { ok: ['✓', 'text-success-700 bg-success-50'], warn: ['!', 'text-warning-700 bg-warning-50'], bad: ['✕', 'text-error-700 bg-error-50'] };
const ul = (a) => a && a.length ? '<ul class="mt-2 space-y-1.5 text-sm text-gray-700">' + a.map((x) => '<li class="flex gap-2"><span class="text-gray-400">•</span><span>' + esc(x) + '</span></li>').join('') + '</ul>' : '';
function renderResult(r) {
    const ai = r.ai, col = r.score >= 80 ? '#12b76a' : r.score >= 60 ? '#FB8A12' : r.score >= 40 ? '#f79009' : '#f04438';
    const groups = {};
    (r.rules || []).forEach((c) => (groups[c.area] ||= []).push(c));
    let h = '<div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm"><div class="flex flex-wrap items-center gap-5">'
        + '<div class="grid size-24 place-items-center rounded-full text-2xl font-bold" style="border:8px solid ' + col + '">' + esc(r.score) + '</div>'
        + '<div class="min-w-0 flex-1"><div class="text-xs uppercase tracking-wide text-gray-400">' + (r.kind === 'product' ? 'Audit produk' : 'Audit toko') + '</div>'
        + '<div class="truncate text-lg font-semibold">' + esc(r.subject?.name) + '</div><div class="text-sm text-gray-500">' + esc(r.subject?.sub) + '</div>'
        + '<span class="mt-2 inline-block rounded-full px-3 py-1 text-xs font-semibold" style="background:' + col + '1a;color:' + col + '">' + esc(r.label) + '</span></div></div>'
        + (r.summary ? '<p class="mt-4 text-sm leading-relaxed text-gray-700">' + esc(r.summary) + '</p>' : '') + '</div>';
    if (ai?.rekomendasi?.length) {
        h += '<div class="mt-4 rounded-2xl border border-gray-200 bg-white p-6"><h3 class="font-semibold">Rekomendasi prioritas</h3><ol class="mt-3 space-y-3">'
            + ai.rekomendasi.map((x, i) => '<li class="flex gap-3"><span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-600">' + (i + 1) + '</span><div class="text-sm"><div class="font-medium text-gray-800">' + esc(x.aksi) + ' <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-[11px] font-medium text-gray-500">' + esc(x.prioritas) + '</span></div>' + (x.alasan ? '<div class="text-gray-500">' + esc(x.alasan) + '</div>' : '') + '</div></li>').join('') + '</ol></div>';
    }
    h += '<div class="mt-4 rounded-2xl border border-gray-200 bg-white p-6"><h3 class="font-semibold">Pemeriksaan otomatis</h3>';
    Object.entries(groups).forEach(([area, items]) => {
        h += '<div class="mt-4"><div class="text-xs font-semibold uppercase tracking-wide text-gray-400">' + esc(area) + '</div><div class="mt-1 divide-y divide-gray-100">'
            + items.map((c) => '<div class="flex gap-3 py-2.5 text-sm"><span class="grid size-6 shrink-0 place-items-center rounded-full text-xs font-bold ' + ICON[c.status][1] + '">' + ICON[c.status][0] + '</span><div><div class="font-medium text-gray-800">' + esc(c.title) + '</div><div class="text-gray-500">' + esc(c.detail) + '</div></div></div>').join('') + '</div></div>';
    });
    h += '</div>';
    if (ai) {
        const blocks = [['Temuan', ai.temuan], ['Peluang', ai.peluang], ['Risiko', ai.risiko]].filter((b) => b[1]?.length);
        if (blocks.length) h += '<div class="mt-4 grid gap-4 md:grid-cols-3">' + blocks.map((b) => '<div class="rounded-2xl border border-gray-200 bg-white p-5"><h3 class="font-semibold">' + b[0] + '</h3>' + ul(b[1]) + '</div>').join('') + '</div>';
        if (ai.saran_judul?.length) h += '<div class="mt-4 rounded-2xl border border-gray-200 bg-white p-6"><h3 class="font-semibold">Saran judul</h3>' + ul(ai.saran_judul) + '</div>';
        if (ai.kata_kunci?.length) h += '<div class="mt-4 rounded-2xl border border-gray-200 bg-white p-6"><h3 class="font-semibold">Kata kunci</h3><div class="mt-3 flex flex-wrap gap-2">' + ai.kata_kunci.map((k) => '<span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700">' + esc(k) + '</span>').join('') + '</div></div>';
    }
    h += '<p class="mt-4 text-center text-xs text-gray-400">Hasil ini dihapus otomatis dari server setelah {{ $hours }} jam. Audit bersifat panduan, bukan jaminan peringkat atau penjualan.</p>';
    $('result').innerHTML = h;
    show('result');
    $('go').disabled = false;
    $('result').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
})();
</script>
@endpush
