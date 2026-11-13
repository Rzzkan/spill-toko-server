@extends('layouts.portal', ['title' => 'Akun Saya'])
@use('App\Support\Fmt')
@section('nav')
<form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="text-sm text-gray-500 hover:text-gray-800">Keluar</button></form>
@endsection
@section('content')
<div class="flex items-center gap-4">
    @if ($c->picture)<img src="{{ $c->picture }}" referrerpolicy="no-referrer" class="size-14 rounded-full" alt="">@endif
    <div><div class="text-lg font-semibold">{{ $c->name ?: $c->email }}</div><div class="text-sm text-gray-500">{{ $c->email }}</div></div>
</div>

@php $active = $apps->filter(fn ($x) => $x['a']['plan'] !== 'none'); @endphp
<h2 class="mt-8 text-lg font-semibold">Langganan aplikasimu <span class="text-sm font-normal text-gray-500">({{ $active->count() }} aplikasi dipakai)</span></h2>
<p class="mt-1 text-sm text-gray-500">Setiap aplikasi TemanSeller punya trial dan langganan sendiri.</p>
<div class="mt-3 grid gap-4 sm:grid-cols-2">
    @foreach ($apps as $x)
        @php
            $app = $x['app']; $a = $x['a']; [$label, $tone] = Fmt::status($a);
            $until = $a['until'] ? \Illuminate\Support\Carbon::createFromTimestamp($a['until']) : null;
            $left = $until ? (int) ceil(now()->diffInSeconds($until, false) / 86400) : null;
        @endphp
        <div class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3"><x-app-icon :app="$app" size="size-12" />
                    <div><div class="font-semibold">{{ $app->name }}</div>@if ($app->tagline)<div class="text-xs text-gray-500">{{ $app->tagline }}</div>@endif</div></div>
                <x-admin.badge :tone="$tone">{{ $label }}</x-admin.badge>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs uppercase text-gray-500">Berlaku sampai</dt><dd class="mt-0.5 font-medium">{{ $a['plan'] === 'lifetime' ? 'Selamanya' : ($until ? Fmt::dt($until, false) : '—') }}</dd></div>
                <div><dt class="text-xs uppercase text-gray-500">Sisa waktu</dt><dd class="mt-0.5 font-medium">@if ($a['plan'] === 'lifetime') ∞ @elseif ($a['access']) {{ $left }} hari @elseif ($a['plan'] === 'none') — @else Sudah berakhir @endif</dd></div>
            </dl>
            <div class="mt-4 flex gap-2">
                @if ($a['access'])
                    <a href="{{ $app->toolUrl() }}" @if ($app->toolUrlExternal()) target="_blank" rel="noopener" @endif class="flex-1 rounded-lg bg-brand-500 px-4 py-2.5 text-center text-sm font-medium text-white hover:bg-brand-600">{{ $app->toolUrlExternal() ? 'Buka di Chrome Web Store' : 'Buka ' . $app->name }}</a>
                @endif
                <a href="{{ route('portal.app', $app->slug) }}" class="rounded-lg {{ $a['access'] ? 'border border-gray-300 text-gray-700 hover:bg-gray-50' : 'flex-1 bg-brand-500 text-white hover:bg-brand-600' }} px-4 py-2.5 text-center text-sm font-medium">
                    {{ $a['plan'] === 'none' ? 'Lihat paket' : ($a['plan'] === 'lifetime' ? 'Lihat detail' : ($a['plan'] === 'monthly' ? 'Perpanjang' : 'Berlangganan')) }}</a>
            </div>
        </div>
    @endforeach
</div>

<h2 class="mt-8 text-lg font-semibold">Browser terhubung <span class="text-sm font-normal text-gray-500">({{ $devices->count() }} dari {{ $maxDevices }})</span></h2>
@if (session('ok'))<div class="mt-3 rounded-lg bg-success-50 p-3 text-sm text-success-700">{{ session('ok') }}</div>@endif
@if (session('error'))<div class="mt-3 rounded-lg bg-error-50 p-3 text-sm text-error-700">{{ session('error') }}</div>@endif
<p class="mt-1 text-sm text-gray-500">Extension hanya bisa dipakai di {{ $maxDevices }} browser. Lepas browser yang tidak dipakai agar bisa masuk dari browser lain; extension di browser yang dilepas akan keluar otomatis.</p>
<div class="mt-3 overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-theme-sm">
    <table class="w-full text-left text-sm">
        <thead><tr class="border-b border-gray-100 text-xs uppercase text-gray-500"><th class="px-5 py-3">Browser</th><th class="px-5 py-3">Terakhir aktif</th><th class="px-5 py-3">IP</th><th class="px-5 py-3"></th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @forelse ($devices as $d)
            <tr><td class="px-5 py-3 font-medium">{{ $d->label ?: 'Browser' }}</td><td class="px-5 py-3 whitespace-nowrap">{{ $d->last_seen_at ? Fmt::dt($d->last_seen_at) . ' WIB' : '—' }}</td><td class="px-5 py-3 text-gray-500">{{ $d->ip ?: '—' }}</td>
                <td class="px-5 py-3 text-right"><form method="POST" action="{{ route('portal.device.revoke', $d->id) }}" onsubmit="return confirm('Lepas browser ini dari akun?')">@csrf @method('DELETE')<button class="rounded-lg border border-error-300 px-3 py-1.5 text-xs font-medium text-error-700 hover:bg-error-50">Lepas</button></form></td></tr>
        @empty <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">Belum ada browser terhubung. Masuk lewat extension untuk mendaftarkan browser.</td></tr> @endforelse
        </tbody>
    </table>
</div>

@if (\App\Services\Layanan::enabled() || $layananSubs->isNotEmpty())
@php $ls = $layananSubs->first(); @endphp
<h2 class="mt-8 text-lg font-semibold">Layanan Sosmed <span class="text-sm font-normal text-gray-500">(kelola Instagram &amp; Threads)</span></h2>
<a href="{{ route('portal.layanan') }}" class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm hover:border-brand-300">
    @if ($ls)
        <div><div class="font-medium">{{ $ls->plan_name }}</div><div class="text-sm text-gray-500">{{ $ls->effectiveStatus() === 'active' && $ls->ends_at ? 'Aktif sampai ' . Fmt::dt($ls->ends_at, false) : $ls->label() }}</div></div>
        <x-admin.badge :tone="$ls->tone()">{{ $ls->label() }}</x-admin.badge>
    @else
        <div><div class="font-medium">Belum berlangganan</div><div class="text-sm text-gray-500">Kami yang kelola konten Instagram &amp; Threads tokomu.</div></div>
        <span class="text-sm font-medium text-brand-500">Lihat layanan →</span>
    @endif
</a>
@endif

<h2 class="mt-8 text-lg font-semibold">Riwayat pembayaran</h2>
<div class="mt-3 overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-theme-sm">
    <table class="w-full text-left text-sm">
        <thead><tr class="border-b border-gray-100 text-xs uppercase text-gray-500"><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Aplikasi</th><th class="px-5 py-3">Paket</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Status</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @forelse ($orders as $o)
            <tr><td class="px-5 py-3 whitespace-nowrap">{{ Fmt::dt($o->created_at) }}</td><td class="px-5 py-3">{{ $appNames[$o->app] ?? $o->app }}</td><td class="px-5 py-3">{{ ($plansByApp[$o->app][$o->plan]['label'] ?? null) ?: ['monthly' => 'Bulanan', 'yearly' => 'Tahunan', 'lifetime' => 'Lifetime'][$o->plan] ?? $o->plan }}</td><td class="px-5 py-3">{{ Fmt::rp($o->amount) }}</td>
                <td class="px-5 py-3"><x-admin.badge :tone="Fmt::orderTone($o->status)">{{ ['paid' => 'Lunas', 'pending' => 'Menunggu', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'denied' => 'Ditolak', 'failed' => 'Gagal'][$o->status] ?? $o->status }}</x-admin.badge></td></tr>
        @empty <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">Belum ada transaksi.</td></tr> @endforelse
        </tbody>
    </table>
</div>
<p class="mt-6 text-xs text-gray-500">Setelah membayar, status langganan diperbarui otomatis dalam hitungan detik. Buka kembali halaman ini bila belum berubah.</p>
@endsection
