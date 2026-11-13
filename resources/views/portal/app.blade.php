@extends('layouts.portal', ['title' => $appRow->name])
@use('App\Support\Fmt')
@section('nav')
<a href="{{ route('portal.account') }}" class="mr-4 text-sm text-gray-500 hover:text-gray-800">← Akun saya</a>
<form method="POST" action="{{ route('portal.logout') }}" class="inline">@csrf<button class="text-sm text-gray-500 hover:text-gray-800">Keluar</button></form>
@endsection
@section('content')
@php
    [$label, $tone] = Fmt::status($a);
    $until = $a['until'] ? \Illuminate\Support\Carbon::createFromTimestamp($a['until']) : null;
    $left = $until ? (int) ceil(now()->diffInSeconds($until, false) / 86400) : null;
    $bg = ['brand' => 'bg-brand-500', 'success' => 'bg-success-500', 'warning' => 'bg-warning-500', 'error' => 'bg-error-500', 'gray' => 'bg-gray-500'][$tone];
    $isLifetime = $a['plan'] === 'lifetime';
@endphp
<div class="flex items-center gap-3">
    <x-app-icon :app="$appRow" size="size-14" />
    <div><h1 class="text-xl font-semibold">{{ $appRow->name }}@if ($appRow->slug === 'shofee') <span class="ml-1 align-middle rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Extension Chrome</span>@endif</h1>@if ($appRow->tagline)<p class="text-sm text-gray-500">{{ $appRow->tagline }}</p>@endif</div>
</div>

<section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm">
    <div class="{{ $bg }} px-6 py-4 text-white">
        <div class="text-sm opacity-90">Status langganan {{ $appRow->name }}</div>
        <div class="text-2xl font-bold">{{ $label }}</div>
    </div>
    <div class="grid gap-4 p-6 sm:grid-cols-3">
        <div><div class="text-xs uppercase text-gray-500">Berlaku sampai</div>
            <div class="mt-1 font-medium">{{ $isLifetime ? 'Selamanya' : ($until ? Fmt::dt($until) . ' WIB' : '—') }}</div></div>
        <div><div class="text-xs uppercase text-gray-500">Sisa waktu</div>
            <div class="mt-1 font-medium">@if ($isLifetime) ∞ @elseif ($a['access']) {{ $left }} hari @elseif ($a['plan'] === 'none') — @else Sudah berakhir @endif</div></div>
        @if ($appRow->slug === 'shofee')<div><div class="text-xs uppercase text-gray-500">Analisis AI hari ini</div>
            <div class="mt-1 font-medium">@if ($ai['enabled']) {{ $ai['used'] }} / {{ $ai['limit'] ?? '∞' }} @else — @endif</div></div>@endif
    </div>
    @if ($a['plan'] === 'none')<p class="border-t border-gray-100 px-6 py-3 text-sm text-gray-500">Kamu belum memakai {{ $appRow->name }}. {{ $trialDays > 0 ? 'Trial ' . $trialDays . ' hari dimulai otomatis saat kamu masuk lewat aplikasinya, atau langsung pilih paket di bawah.' : 'Mulai dari aplikasinya, atau pilih paket di bawah bila perlu.' }}</p>
    @elseif ($a['plan'] === 'trial')<p class="border-t border-gray-100 px-6 py-3 text-sm text-gray-500">Kamu sedang dalam masa trial {{ $trialDays }} hari. Berlangganan agar akses tidak terputus setelah trial berakhir.</p>
    @elseif (!$a['access'])<p class="border-t border-gray-100 bg-error-50 px-6 py-3 text-sm text-error-700">Akses kamu sudah berakhir. Perpanjang di bawah untuk memakai kembali {{ $appRow->name }}.</p>
    @elseif ($a['plan'] === 'monthly' && $left <= 5)<p class="border-t border-gray-100 bg-warning-50 px-6 py-3 text-sm text-warning-700">Langganan segera berakhir. Perpanjang sekarang — sisa hari kamu tetap dihitung.</p>@endif
</section>

<h2 class="mt-8 text-lg font-semibold">{{ $a['plan'] === 'lifetime' ? 'Paket kamu' : ($a['plan'] === 'monthly' ? 'Perpanjang langganan' : 'Pilih paket') }}</h2>
@if (!$canPay)<div class="mt-3 rounded-lg bg-warning-50 p-4 text-sm text-warning-700">Pembayaran belum tersedia. Hubungi admin.</div>@endif
<div class="mt-3 grid gap-4 sm:grid-cols-2">
    @foreach (collect($plans)->only(['monthly', 'yearly'])->keys() as $k)
        @php $pl = $plans[$k]; $disc = \App\Services\Plans::discount($pl); @endphp
        <div class="rounded-2xl border {{ $k === 'yearly' ? 'border-brand-300' : 'border-gray-200' }} bg-white p-6 shadow-theme-sm">
            <div class="flex items-center justify-between text-sm text-gray-500"><span>{{ $pl['label'] }}</span>@if ($disc)<span class="rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700">Hemat {{ $disc }}%</span>@endif</div>
            <div class="mt-1 flex items-baseline gap-2">
                @if ($pl['original'] > $pl['price'])<s class="text-sm text-gray-400">{{ Fmt::rp($pl['original']) }}</s>@endif
                <span class="text-2xl font-bold">{{ Fmt::rp($pl['price']) }}</span><span class="text-sm font-normal text-gray-500">/ {{ $k === 'yearly' ? 'tahun' : 'bulan' }}</span>
            </div>
            <p class="mt-2 text-sm text-gray-500">{{ $a['plan'] === 'monthly' ? 'Ditambahkan setelah masa aktif saat ini berakhir.' : ($appRow->slug === 'shofee' ? 'Semua fitur riset + analisis AI.' : 'Semua fitur ' . $appRow->name . '.') }}
                @if ($appRow->slug === 'shofee' && $ai['enabled']) <br>Analisis AI: <b>{{ ($q = $ai['quotas'][$k] ?? null) === null ? 'tanpa batas' : number_format($q, 0, ',', '.') . ' analisis' }}/hari</b>@endif</p>
            <form method="POST" action="{{ route('portal.pay', $appRow->slug) }}" class="mt-4">@csrf<input type="hidden" name="plan" value="{{ $k }}">
                <button @disabled(!$canPay || $isLifetime) class="w-full rounded-lg {{ $k === 'yearly' ? 'bg-gray-900 hover:bg-gray-800' : 'bg-brand-500 hover:bg-brand-600' }} px-4 py-3 text-sm font-medium text-white disabled:opacity-50">{{ $a['plan'] === 'monthly' ? 'Perpanjang ' . $pl['label'] : 'Pilih ' . $pl['label'] }}</button></form>
        </div>
    @endforeach
</div>
@if (isset($plans['lifetime']) && !$isLifetime)
    @php $lt = $plans['lifetime']; $discLt = \App\Services\Plans::discount($lt); @endphp
    <div class="mt-4 rounded-2xl border-2 border-brand-300 bg-white p-6 shadow-theme-sm sm:max-w-sm">
        <div class="flex items-center justify-between text-sm text-gray-500"><span>{{ $lt['label'] }}</span>@if ($discLt)<span class="rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700">Hemat {{ $discLt }}%</span>@endif</div>
        <div class="mt-1 flex items-baseline gap-2">
            @if ($lt['original'] > $lt['price'])<s class="text-sm text-gray-400">{{ Fmt::rp($lt['original']) }}</s>@endif
            <span class="text-2xl font-bold">{{ Fmt::rp($lt['price']) }}</span><span class="text-sm font-normal text-gray-500">sekali bayar</span>
        </div>
        <p class="mt-2 text-sm text-gray-500">Akses selamanya untuk {{ $appRow->name }} — bukan langganan bulanan.</p>
        <form method="POST" action="{{ route('portal.pay', $appRow->slug) }}" class="mt-4">@csrf<input type="hidden" name="plan" value="lifetime">
            <button @disabled(!$canPay) class="w-full rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">Beli {{ $lt['label'] }}</button></form>
    </div>
@endif
@if ($isLifetime)<p class="mt-3 text-sm text-gray-500">Kamu memiliki akses Lifetime untuk {{ $appRow->name }} — tidak perlu berlangganan.</p>@endif
@if (!count($plans))<p class="mt-3 text-sm text-gray-500">Paket {{ $appRow->name }} belum dibuka.</p>@endif


<h2 class="mt-8 text-lg font-semibold">Riwayat pembayaran {{ $appRow->name }}</h2>
<div class="mt-3 overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-theme-sm">
    <table class="w-full text-left text-sm">
        <thead><tr class="border-b border-gray-100 text-xs uppercase text-gray-500"><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Paket</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Status</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @forelse ($orders as $o)
            <tr><td class="px-5 py-3 whitespace-nowrap">{{ Fmt::dt($o->created_at) }}</td><td class="px-5 py-3">{{ $plans[$o->plan]['label'] ?? $o->plan }}</td><td class="px-5 py-3">{{ Fmt::rp($o->amount) }}</td>
                <td class="px-5 py-3"><x-admin.badge :tone="Fmt::orderTone($o->status)">{{ ['paid' => 'Lunas', 'pending' => 'Menunggu', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'denied' => 'Ditolak', 'failed' => 'Gagal'][$o->status] ?? $o->status }}</x-admin.badge></td></tr>
        @empty <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">Belum ada transaksi.</td></tr> @endforelse
        </tbody>
    </table>
</div>
<p class="mt-6 text-xs text-gray-500">Setelah membayar, status diperbarui otomatis dalam hitungan detik. Buka kembali halaman ini bila belum berubah.</p>
@endsection
