@extends('layouts.portal', ['title' => 'Layanan Sosmed'])
@use('App\Support\Fmt')
@section('nav')
<a href="{{ route('portal.account') }}" class="mr-4 text-sm text-gray-500 hover:text-gray-800">← Akun saya</a>
<form method="POST" action="{{ route('portal.logout') }}" class="inline">@csrf<button class="text-sm text-gray-500 hover:text-gray-800">Keluar</button></form>
@endsection
@section('content')
@php $waUrl = \App\Services\Layanan::waUrl('Halo TemanSeller, saya ' . ($c->name ?: $c->email) . ' ingin konfirmasi pesanan layanan kelola Instagram & Threads.'); @endphp
<div>
    <h1 class="text-xl font-semibold">Layanan Sosmed</h1>
    <p class="text-sm text-gray-500">Kelola Instagram &amp; Threads oleh tim TemanSeller.</p>
</div>

@if ($pending)
<section class="mt-6 rounded-2xl border-2 border-brand-300 bg-white p-6 shadow-theme-sm">
    <div class="text-sm font-medium text-brand-600">Konfirmasi pesanan</div>
    <div class="mt-1 text-lg font-semibold">{{ $pending->type->name }} – {{ $pending->name }}</div>
    <div class="text-2xl font-bold">{{ Fmt::rp($pending->price) }} <span class="text-sm font-normal text-gray-500">{{ $pending->period }}</span></div>
    <ul class="mt-3 space-y-1 text-sm text-gray-600">@foreach ($pending->featureList() as $f)<li>✓ {{ $f }}</li>@endforeach</ul>
    <form method="POST" action="{{ route('portal.layanan.order') }}" class="mt-4">@csrf
        <input type="hidden" name="plan" value="{{ $pending->id }}">
        <label class="block text-sm font-medium text-gray-700">Catatan untuk kami <span class="font-normal text-gray-400">(opsional)</span></label>
        <textarea name="note" rows="3" maxlength="1000" class="mt-1 w-full rounded-lg border border-gray-300 p-3 text-sm" placeholder="Nama akun Instagram/Threads, jenis produk, gaya yang disukai…"></textarea>
        <div class="mt-3 flex gap-3"><button class="rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">Konfirmasi pesanan</button><a href="{{ route('layanan.sosmed') }}" class="rounded-lg border border-gray-300 px-5 py-3 text-sm text-gray-700">Batal</a></div>
    </form>
</section>
@endif

@if (!$enabled && $subs->isEmpty())
    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500">Layanan ini sedang tidak dibuka.</div>
@endif

@forelse ($subs as $s)
    @php $st = $s->effectiveStatus(); $bg = ['pending' => 'bg-warning-500', 'active' => 'bg-success-500', 'expired' => 'bg-error-500', 'cancelled' => 'bg-gray-500'][$st] ?? 'bg-gray-500'; @endphp
    <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="{{ $bg }} px-6 py-4 text-white">
            <div class="text-sm opacity-90">Status langganan layanan</div>
            <div class="text-2xl font-bold">{{ $s->label() }}</div>
        </div>
        <div class="grid gap-4 p-6 sm:grid-cols-3">
            <div><div class="text-xs uppercase text-gray-500">Jenis paket</div><div class="mt-1 font-medium">{{ $s->plan_name }}</div></div>
            <div><div class="text-xs uppercase text-gray-500">Harga</div><div class="mt-1 font-medium">{{ $s->price > 0 ? Fmt::rp($s->price) : '—' }}</div></div>
            <div><div class="text-xs uppercase text-gray-500">Berlaku sampai</div><div class="mt-1 font-medium">{{ $s->ends_at ? Fmt::dt($s->ends_at, false) : '—' }}</div></div>
        </div>
        <div class="border-t border-gray-100 p-6">
            <div class="text-xs uppercase text-gray-500">Folder Drive hasil kerja</div>
            @php $drive = \App\Services\Layanan::safeUrl($s->drive_url); @endphp
            @if ($drive)<a href="{{ $drive }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex items-center gap-2 font-medium text-brand-500 hover:underline">Buka folder Drive ↗</a>
                <div class="mt-1 break-all text-xs text-gray-400">{{ $drive }}</div>
            @else<div class="mt-1 text-sm text-gray-500">{{ $st === 'pending' ? 'Tersedia setelah paket diaktifkan.' : 'Admin belum menautkan folder Drive.' }}</div>@endif
        </div>
        @if ($st === 'pending')
        <div class="border-t border-gray-100 bg-warning-50 p-6 text-sm text-gray-700">
            <div class="font-medium">Langkah selanjutnya</div>
            <p class="mt-1">Lakukan pembayaran {{ Fmt::rp($s->price) }} lalu konfirmasi ke admin. Paket aktif setelah pembayaran diterima.</p>
            @if ($payment)<pre class="mt-3 whitespace-pre-wrap rounded-lg bg-white p-3 font-sans text-sm">{{ $payment }}</pre>@endif
            @if ($waUrl)<a href="{{ $waUrl }}" target="_blank" rel="noopener" class="mt-3 inline-block rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-success-600">Konfirmasi via WhatsApp</a>@endif
        </div>
        @endif
        <form method="POST" action="{{ route('portal.layanan.note', $s->id) }}" class="border-t border-gray-100 p-6">@csrf
            <label class="block text-sm font-medium text-gray-700">Catatan untuk tim</label>
            <textarea name="note" rows="2" maxlength="1000" class="mt-1 w-full rounded-lg border border-gray-300 p-3 text-sm">{{ $s->customer_note }}</textarea>
            <button class="mt-2 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Simpan catatan</button>
        </form>
        @if ($s->admin_note)<div class="border-t border-gray-100 p-6 text-sm"><div class="text-xs uppercase text-gray-500">Pesan dari admin</div><p class="mt-1 whitespace-pre-line text-gray-700">{{ $s->admin_note }}</p></div>@endif
    </section>
@empty
    @if ($enabled && !$pending)
    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-8 text-center">
        <div class="text-3xl">📸</div>
        <h2 class="mt-2 font-semibold">Belum ada langganan layanan</h2>
        <p class="mt-1 text-sm text-gray-500">Pilih paket kelola Instagram &amp; Threads yang sesuai tokomu.</p>
        <a href="{{ route('layanan.sosmed') }}" class="mt-4 inline-block rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">Lihat paket</a>
    </section>
    @endif
@endforelse

@if ($subs->isNotEmpty() && $enabled)
    <p class="mt-6 text-sm text-gray-500">Mau tambah atau ganti paket? <a href="{{ route('layanan.sosmed') }}#paket" class="text-brand-500">Lihat paket</a>.</p>
@endif
@endsection
