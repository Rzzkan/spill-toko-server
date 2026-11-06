@extends('layouts.portal', ['title' => 'Masuk'])
@section('content')
<div class="mx-auto max-w-md rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-theme-sm">
    <h1 class="text-title-sm font-semibold">Masuk ke akunmu</h1>
    <p class="mt-2 text-sm text-gray-500">Gunakan akun Google yang sama dengan yang dipakai di extension Shofee untuk melihat status langganan dan memperpanjangnya.</p>
    @if ($clientId === '')
        <div class="mt-6 rounded-lg bg-warning-50 p-4 text-sm text-warning-700">Login Google belum dikonfigurasi. Hubungi admin.</div>
    @else
        <div id="g_id_onload" data-client_id="{{ $clientId }}" data-callback="onGoogle" data-nonce="{{ $nonce }}" data-auto_prompt="false" data-ux_mode="popup"></div>
        <div class="mt-6 flex justify-center"><div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="signin_with" data-shape="pill" data-logo_alignment="left" data-locale="id"></div></div>
        <p id="msg" class="mt-4 hidden text-sm text-error-600"></p>
    @endif
</div>
@endsection
@push('scripts')
@if ($clientId !== '')
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
async function onGoogle(resp) {
    const msg = document.getElementById('msg');
    msg.classList.add('hidden');
    try {
        const r = await fetch('{{ route('portal.login.google') }}', { method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ credential: resp.credential }) });
        const j = await r.json();
        if (r.ok && j.redirect) { location.href = j.redirect; return; }
        msg.textContent = j.error || 'Gagal masuk, muat ulang halaman lalu coba lagi.';
    } catch (e) { msg.textContent = 'Tidak bisa menghubungi server.'; }
    msg.classList.remove('hidden');
}
</script>
@endif
@endpush
