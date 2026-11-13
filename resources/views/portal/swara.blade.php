@extends('layouts.portal', ['title' => 'Swara'])
@section('nav')
<a href="{{ route('portal.account') }}" class="mr-4 text-sm text-gray-500 hover:text-gray-800">← Akun saya</a>
<form method="POST" action="{{ route('portal.logout') }}" class="inline">@csrf<button class="text-sm text-gray-500 hover:text-gray-800">Keluar</button></form>
@endsection
@section('content')
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-xl font-semibold">🎙️ Swara</h1>
        <p class="text-sm text-gray-500">Cloning suara. Mesinnya jalan di Google Colab kamu sendiri — audio tidak pernah melewati server TemanSeller.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('swara.guide') }}" class="rounded-xl border border-ts-orange/60 bg-ts-cream px-4 py-2 text-sm font-semibold text-ts-navy hover:bg-orange-100">📖 Panduan penggunaan</a>
        <div id="status" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-gray-500">Memeriksa notebook…</div>
    </div>
</div>

<section class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
    <div id="connectBox">
        <div class="text-sm font-medium text-gray-700">1. Sambungkan notebook Colab</div>
        <p class="mt-1 text-sm text-gray-500">Belum punya notebooknya? <a href="{{ $notebookUrl }}" target="_blank" rel="noopener" class="font-semibold text-ts-orange-text hover:underline">Buka notebook Swara di Google Colab</a>, jalankan sampai selesai (Runtime → Run all), lalu salin link yang muncul di sel terakhir dan tempel di sini.</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <input id="pasteLink" type="text" placeholder="Tempel link dari notebook di sini" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            <button id="btnConnect" class="rounded-lg bg-ts-orange px-4 py-2.5 text-sm font-semibold text-ts-navy hover:bg-ts-orange-dark">Sambungkan</button>
        </div>
        <p id="connectMsg" class="mt-2 text-sm"></p>
    </div>

    <div id="toolBox" class="mt-6 hidden border-t border-gray-100 pt-6">
        <div class="text-sm font-medium text-gray-700">2. Contoh suara</div>
        <input id="refAudio" type="file" accept="audio/*" class="mt-2 block w-full rounded-lg border border-dashed border-gray-300 p-3 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-ts-cream file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ts-navy">
        <p class="mt-1 text-xs text-gray-500">Rekaman jernih 10–30 detik, suara sendiri atau suara yang kamu punya izin memakainya.</p>

        <div class="mt-5 text-sm font-medium text-gray-700">3. Naskah</div>
        <textarea id="text" rows="4" maxlength="2000" placeholder="Tulis naskah yang mau diucapkan…" class="mt-2 block w-full rounded-lg border border-gray-300 p-3 text-sm"></textarea>

        <label class="mt-4 flex items-start gap-2 text-sm text-gray-600">
            <input id="consent" type="checkbox" class="mt-0.5 accent-orange-500">
            <span>Saya memiliki suara ini atau izin pemiliknya untuk meng-clone-nya, dan tidak akan memakainya untuk menipu atau menyamar sebagai orang lain.</span>
        </label>

        <button id="btnGenerate" disabled class="mt-4 rounded-lg bg-ts-orange px-5 py-2.5 text-sm font-semibold text-ts-navy hover:bg-ts-orange-dark disabled:cursor-not-allowed disabled:opacity-40">Hasilkan suara</button>
        <p id="genMsg" class="mt-2 text-sm"></p>

        <div id="result" class="mt-5 hidden rounded-xl bg-ts-cream p-4">
            <audio id="player" controls class="w-full"></audio>
            <a id="dl" download="swara.wav" class="mt-2 inline-block text-sm font-semibold text-ts-orange-text hover:underline">Unduh berkas</a>
        </div>
    </div>
</section>

<div class="mt-6 rounded-2xl bg-gray-50 p-5 text-sm text-gray-500">
    <b class="text-gray-700">Privasi.</b> Contoh suara dan naskahmu dikirim langsung dari browser ke Colab kamu sendiri, tidak lewat server TemanSeller. Server kami hanya memeriksa lisensi dan menerbitkan izin pakai yang berumur 15 menit; diperpanjang otomatis selama tab ini terbuka. Menjalankan notebook baru akan mengambil alih dari sesi lama.
</div>
@endsection
@push('scripts')
<script>
(function () {
    const TTL_MS = {{ $ttl * 1000 }};
    const PAIR_URL = @json(route('swara.pair'));
    const TOKEN_URL = @json(route('swara.token'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let sid = null, colabBase = null, token = null, tokenTimer = null;

    const $ = (id) => document.getElementById(id);
    const statusEl = $('status'), connectMsg = $('connectMsg'), genMsg = $('genMsg');

    function setStatus(text, ok) {
        statusEl.textContent = text;
        statusEl.className = 'rounded-xl border px-4 py-2 text-sm ' + (ok === true ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ok === false ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-gray-200 bg-white text-gray-500');
    }

    function saveSession() {
        try { sessionStorage.setItem('swara_sid', sid || ''); sessionStorage.setItem('swara_base', colabBase || ''); } catch (e) {}
    }

    function restoreSession() {
        try { sid = sessionStorage.getItem('swara_sid') || null; colabBase = sessionStorage.getItem('swara_base') || null; } catch (e) {}
    }

    // Link dari notebook: https://temanseller.com/akun/swara#sid=XXXX&u=<base64 dari URL tunnel>
    function parseLink(raw) {
        try {
            const h = raw.includes('#') ? raw.split('#').slice(1).join('#') : raw;
            const p = new URLSearchParams(h);
            const s = p.get('sid'), u = p.get('u');
            if (!s || !u) return null;
            return { sid: s, base: atob(u).replace(/\/$/, '') };
        } catch (e) { return null; }
    }

    async function pairAndStart(newSid, newBase) {
        sid = newSid; colabBase = newBase; saveSession();
        connectMsg.textContent = 'Menyambungkan…'; connectMsg.className = 'mt-2 text-sm text-gray-500';
        try {
            const r = await fetch(PAIR_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: JSON.stringify({ sid }) });
            const j = await r.json();
            if (!r.ok) throw new Error(j.error || 'Gagal menyambungkan');
            connectMsg.textContent = 'Tersambung.'; connectMsg.className = 'mt-2 text-sm text-emerald-600';
            $('toolBox').classList.remove('hidden');
            await refreshToken();
            scheduleRefresh();
        } catch (e) {
            connectMsg.textContent = e.message; connectMsg.className = 'mt-2 text-sm text-rose-600';
            setStatus('Belum terhubung', false);
        }
    }

    async function refreshToken() {
        if (!sid) return;
        try {
            const r = await fetch(TOKEN_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: JSON.stringify({ sid }) });
            const j = await r.json();
            if (!r.ok) {
                token = null;
                setStatus(j.error || 'Sesi berakhir', false);
                clearTimeout(tokenTimer);
                return;
            }
            token = j.token;
            setStatus('Notebook tersambung', true);
        } catch (e) {
            setStatus('Tidak bisa menghubungi server TemanSeller', false);
        }
    }

    function scheduleRefresh() {
        clearTimeout(tokenTimer);
        tokenTimer = setTimeout(async () => { await refreshToken(); scheduleRefresh(); }, Math.max(60000, TTL_MS - 180000));
    }

    $('btnConnect').addEventListener('click', () => {
        const link = $('pasteLink').value.trim();
        const parsed = parseLink(link || location.hash);
        if (!parsed) { connectMsg.textContent = 'Link tidak dikenali. Salin ulang dari sel terakhir notebook.'; connectMsg.className = 'mt-2 text-sm text-rose-600'; return; }
        pairAndStart(parsed.sid, parsed.base);
    });

    $('refAudio').addEventListener('change', updateGenBtn);
    $('text').addEventListener('input', updateGenBtn);
    $('consent').addEventListener('change', updateGenBtn);
    function updateGenBtn() {
        $('btnGenerate').disabled = !($('refAudio').files[0] && $('text').value.trim() && $('consent').checked && token);
    }

    $('btnGenerate').addEventListener('click', async () => {
        if (!token || !colabBase) return;
        genMsg.textContent = 'Menghasilkan suara… bisa beberapa puluh detik.'; genMsg.className = 'mt-2 text-sm text-gray-500';
        $('btnGenerate').disabled = true;
        try {
            const fd = new FormData();
            fd.append('ref', $('refAudio').files[0]);
            fd.append('text', $('text').value.trim());
            const resp = await fetch(colabBase + '/generate', { method: 'POST', headers: { Authorization: 'Bearer ' + token }, body: fd });
            if (!resp.ok) {
                let msg = 'Gagal membuat audio (' + resp.status + ')';
                try { const j = await resp.json(); if (j.error) msg = j.error; } catch (e) {}
                throw new Error(msg);
            }
            const blob = await resp.blob();
            const url = URL.createObjectURL(blob);
            $('player').src = url; $('dl').href = url;
            $('result').classList.remove('hidden');
            genMsg.textContent = 'Selesai.'; genMsg.className = 'mt-2 text-sm text-emerald-600';
        } catch (e) {
            genMsg.textContent = e.message + ' — pastikan notebook masih berjalan.'; genMsg.className = 'mt-2 text-sm text-rose-600';
        } finally {
            updateGenBtn();
        }
    });

    // Muat otomatis bila link dibawa lewat hash URL (dari notebook yang membuka tab baru).
    (function init() {
        const fromHash = location.hash ? parseLink(location.href) : null;
        if (fromHash) {
            history.replaceState(null, '', location.pathname);
            pairAndStart(fromHash.sid, fromHash.base);
            return;
        }
        restoreSession();
        if (sid && colabBase) { $('toolBox').classList.remove('hidden'); refreshToken().then(scheduleRefresh); }
        else setStatus('Belum terhubung', false);
    })();
})();
</script>
@endpush
