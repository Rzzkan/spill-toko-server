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
        <p class="text-sm text-gray-500">Audit toko atau produk Shopee, dibantu AI. Unggah screenshot atau halaman tersimpan, kami periksa.</p>
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
    <form id="upform" enctype="multipart/form-data" novalidate>
        <div class="text-sm font-medium text-gray-700">1. Apa yang mau diaudit?</div>
        <div class="mt-2 flex gap-3">
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm has-[:checked]:border-ts-orange has-[:checked]:bg-ts-cream"><input type="radio" name="kind" value="product" checked class="accent-orange-500"> Produk</label>
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm has-[:checked]:border-ts-orange has-[:checked]:bg-ts-cream"><input type="radio" name="kind" value="shop" class="accent-orange-500"> Toko</label>
        </div>

<div class="mt-5 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="pages">2a. Halaman tersimpan <span class="font-normal text-gray-400">(.html / .mhtml, maks 2)</span></label>
                <input id="pages" name="pages[]" type="file" multiple accept=".html,.htm,.mhtml,.mht" class="mt-2 block w-full rounded-lg border border-dashed border-gray-300 p-3 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-ts-cream file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ts-navy">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="shots">2b. Screenshot <span class="font-normal text-gray-400">(JPG / PNG / WEBP, maks 4)</span></label>
                <input id="shots" name="shots[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-lg border border-dashed border-gray-300 p-3 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-ts-cream file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ts-navy">
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-500">Cukup salah satu: halaman tersimpan atau screenshot. Kalau dua-duanya diunggah, hasilnya biasanya lebih lengkap.</p>

        <button id="go" type="submit" class="mt-5 rounded-lg bg-ts-orange px-6 py-3 text-sm font-semibold text-ts-navy hover:bg-ts-orange-dark disabled:opacity-50">Mulai audit</button>
        <p id="formerr" class="mt-3 hidden text-sm text-error-600"></p>
    </form>
    @if (!$q['can'])
        <div class="mt-4 rounded-lg bg-warning-50 p-4 text-sm text-warning-700">{{ $q['reason'] }}
            @if (!$q['unlimited'] && collect($plans)->contains(fn ($p) => ($p['price'] ?? 0) > 0))<a class="font-semibold underline" href="{{ route('portal.app', 'audit') }}">Lihat paket unlimited</a>@endif
        </div>
    @endif
    <div class="mt-5 rounded-lg bg-gray-50 p-4 text-xs leading-relaxed text-gray-600">
        <b class="text-gray-800">Privasi:</b> berkas yang kamu unggah <b>tidak disimpan</b>. Isinya hanya dibaca sekali di memori untuk mengambil angka dan teks yang tampil (harga, rating, judul, dll.), lalu langsung dibuang. Kami tidak meminta kata sandi atau akses akun Shopee-mu. Yang tersimpan hanya data hasil bacaan dan hasil audit, dihapus otomatis setelah <b>{{ $hours }} jam</b>; yang tersisa hanya hitungan jatah scan.
    </div>
</section>

<section class="mt-6 rounded-2xl border border-gray-200 bg-white p-6">
    <h2 class="font-semibold text-ts-navy">Cara mengambil bahan audit</h2>
    <p class="mt-1 text-sm text-gray-500">Pilih salah satu cara. Dua-duanya hanya butuh browser dan HP/laptop yang kamu pakai sehari-hari.</p>
    <div class="mt-4 grid gap-5 md:grid-cols-2">
        <div class="rounded-xl bg-ts-cream/60 p-5">
            <div class="text-sm font-semibold text-ts-navy">Cara A · Simpan halaman (laptop)</div>
            <ol class="mt-3 space-y-2 text-sm text-gray-700">
                <li><b>1.</b> Buka halaman produk atau toko di Shopee lewat Chrome/Edge di laptop. Login dulu kalau diminta.</li>
                <li><b>2.</b> Tunggu sampai halaman termuat penuh. Gulir pelan ke bawah sampai deskripsi dan ulasan terlihat.</li>
                <li><b>3.</b> Tekan <kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">Ctrl</kbd> + <kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">S</kbd> (Mac: <kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">⌘</kbd> + <kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">S</kbd>).</li>
                <li><b>4.</b> Pada "Save as type" / "Format", pilih <b>Webpage, Single File (.mhtml)</b>. Kalau tidak ada, pilih <b>Webpage, Complete</b>. Simpan.</li>
                <li><b>5.</b> Unggah berkas <code>.mhtml</code> atau <code>.html</code>-nya di kolom 2a.</li>
            </ol>
            <p class="mt-3 text-xs text-gray-500">Hasil paling lengkap. Untuk toko, simpan halaman toko (bagian "Semua Produk" ikut terbaca).</p>
        </div>
        <div class="rounded-xl bg-ts-cream/60 p-5">
            <div class="text-sm font-semibold text-ts-navy">Cara B · Screenshot (HP atau laptop)</div>
            <ol class="mt-3 space-y-2 text-sm text-gray-700">
                <li><b>1.</b> Buka halaman produk atau toko di aplikasi/browser Shopee.</li>
                <li><b>2.</b> Ambil screenshot bagian <b>atas</b> (judul, harga, rating, terjual, variasi).</li>
                <li><b>3.</b> Gulir, lalu screenshot <b>deskripsi</b> dan <b>ulasan</b> pembeli. Untuk toko: profil toko dan daftar produknya.</li>
                <li><b>4.</b> Unggah maksimal 4 gambar di kolom 2b. Pastikan teks terbaca jelas, tidak buram, tidak terpotong.</li>
            </ol>
            <p class="mt-3 text-xs text-gray-500">Cara tombol screenshot: HP, tekan tombol daya + volume bawah. Windows: <kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">Win</kbd>+<kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">Shift</kbd>+<kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">S</kbd>. Mac: <kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">⌘</kbd>+<kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">Shift</kbd>+<kbd class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-gray-300">4</kbd>.</p>
        </div>
    </div>
    <div class="mt-4 rounded-lg bg-gray-50 p-4 text-xs leading-relaxed text-gray-600">
        <b class="text-gray-800">Tips biar hasilnya akurat:</b> pastikan angka (harga, terjual, rating) terlihat, jangan ada notifikasi menutupi layar, dan jangan sertakan data pribadimu (alamat, nomor HP, saldo). Kalau ada bagian yang tidak terbaca, bagian itu dilewati, bukan ditebak.
        <br>Audit tidak berjalan? Coba Cara B, atau unggah ulang setelah halaman termuat penuh.
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
let scan = null, timer = null, analyzing = false;
const show = (...ids) => ['progress', 'fail', 'result'].forEach((i) => $(i) && $(i).classList.toggle('hidden', !ids.includes(i)));
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
async function api(method, path, body) {
    const isForm = body instanceof FormData;
    const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf };
    if (body && !isForm) headers['Content-Type'] = 'application/json';
    const r = await fetch(path, { method, headers, body: body ? (isForm ? body : JSON.stringify(body)) : undefined });
    const j = await r.json().catch(() => ({}));
    if (!r.ok) throw Object.assign(new Error((j.errors && Object.values(j.errors)[0]?.[0]) || j.error || j.message || 'Terjadi kesalahan (' + r.status + ')'), { status: r.status });
    return j;
}

function setQuota(q) { if (!q) return; const el = $('left'); if (el && q.left !== null) el.textContent = q.left; }
function ferr(m) { const e = $('formerr'); e.textContent = m; e.classList.toggle('hidden', !m); }

const STAGES = { reading: ['Membaca berkasmu…', 'Berkas hanya dibaca lalu dibuang. Biasanya 10–60 detik, jangan tutup halaman ini.'], collected: ['Data terbaca', 'Menyiapkan analisis…'], analyzing: ['AI sedang menganalisis…', 'Biasanya 10–40 detik.'] };
function poll() {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        if (!scan) return;
        try { const s = await api('GET', '{{ url('/audit/scans') }}/' + scan.id); setQuota(s.quota); handle(s); }
        catch (e) { if (e.status === 404) { reset(); } else poll(); }
    }, 2000);
}
function handle(s) {
    if (s.status === 'analyzing') { stage('analyzing'); return poll(); }
    if (s.status === 'collected') { stage('collected'); if (!analyzing) runAnalyze(); return poll(); }
    if (s.status === 'done') { scan = null; renderResult(s.result); return; }
    if (['failed', 'expired', 'cancelled'].includes(s.status)) {
        $('failmsg').textContent = s.status === 'cancelled' ? 'Pemindaian dibatalkan.' : (s.error || 'Pemindaian gagal.');
        $('retry').classList.toggle('hidden', !(s.status === 'failed' && s.error && /AI gagal/.test(s.error) && !/unggah ulang/.test(s.error)));
        show('fail'); if (s.status !== 'failed' || /unggah ulang/.test(s.error || '')) scan = null; $('go').disabled = false;
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

$('upform')?.addEventListener('submit', async (ev) => {
    ev.preventDefault(); ferr('');
    const f = ev.target, np = f.pages.files.length, ns = f.shots.files.length;
    if (!np && !ns) return ferr('Unggah minimal satu halaman tersimpan atau satu screenshot. Lihat cara di bawah.');
    if (np > 2 || ns > 4) return ferr('Maksimal 2 halaman tersimpan dan 4 screenshot.');
    $('go').disabled = true; stage('reading');
    try {
        const s = await api('POST', '{{ route('audit.upload') }}', new FormData(f));
        f.pages.value = ''; f.shots.value = '';   // lepas referensi berkas dari form
        scan = s; setQuota(s.quota); handle(s);
    } catch (e) { show(); ferr(e.message); $('go').disabled = false; }
});
$('retry')?.addEventListener('click', async () => { $('fail').classList.add('hidden'); stage('analyzing'); try { await runAnalyze(); } catch (_) {} });
document.querySelectorAll('.cancel').forEach((b) => b.addEventListener('click', async () => { if (scan) { try { await api('POST', '{{ url('/audit/scans') }}/' + scan.id + '/cancel'); } catch (_) {} } reset(); }));
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
