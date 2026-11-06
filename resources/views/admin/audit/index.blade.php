@extends('layouts.app', ['title' => 'Marketplace Audit'])
@use('App\Support\Fmt')
@section('content')
<x-common.page-breadcrumb pageTitle="Marketplace Audit" />
@if (session('ok'))<div class="mb-4 rounded-lg bg-success-50 p-3 text-sm text-success-700">{{ session('ok') }}</div>@endif
<div class="mb-4 flex flex-wrap gap-2">
    <x-admin.btn size="sm" :href="route('admin.settings', 'audit')">Atur jatah & batas</x-admin.btn>
    @if ($app)<x-admin.btn size="sm" tone="outline" :href="route('admin.apps.edit', $app)">Atur paket & harga langganan</x-admin.btn>@endif
    <x-admin.btn size="sm" tone="outline" :href="url('/marketplace-audit')">Lihat halaman publik ↗</x-admin.btn>
</div>
<div class="grid grid-cols-2 gap-4 md:gap-6 xl:grid-cols-4">
    <x-admin.stat label="Scan (30 hr)" :value="number_format($totals['all'])" :hint="$totals['product'] . ' produk · ' . $totals['shop'] . ' toko'" tone="brand" />
    <x-admin.stat label="Berhasil" :value="number_format($totals['done'])" :hint="$totals['users'] . ' pengguna'" tone="success" />
    <x-admin.stat label="Gagal / kedaluwarsa" :value="number_format($totals['failed'])" :hint="$totals['login'] . ' terhenti belum login Shopee'" :tone="$totals['failed'] ? 'warning' : 'gray'" />
    <x-admin.stat label="Pelanggan unlimited" :value="number_format($subs['monthly'] + $subs['lifetime'])" :hint="$subs['expired'] . ' berakhir'" tone="brand" />
</div>
<div class="mt-6 grid gap-4 md:gap-6 xl:grid-cols-3">
    <x-admin.card title="Scan berhasil per hari" desc="30 hari terakhir" class="xl:col-span-2"><div data-chart='@json($chart)'></div></x-admin.card>
    <x-admin.card title="Aturan saat ini">
        <div class="space-y-2 px-5 pb-5 text-sm text-gray-600 dark:text-gray-400 sm:px-6">
            <div>Status: <b>{{ $enabled ? 'Dibuka' : 'Ditutup' }}</b></div>
            <div>Jatah gratis: <b>{{ $free }}</b> scan / bulan (reset tgl 1, WIB)</div>
            <div>Batas harian berlangganan: <b>{{ $cap > 0 ? $cap . ' scan' : 'tanpa batas' }}</b></div>
            <div>Masa simpan hasil: <b>{{ $hours }} jam</b></div>
            <div class="pt-2 font-medium text-gray-800 dark:text-white/90">Paket langganan</div>
            @foreach ($plans as $k => $p)<div>{{ $p['label'] ?? ucfirst($k) }}: {{ ($p['price'] ?? 0) > 0 ? Fmt::rp($p['price']) . ' / ' . ($p['days'] ?? '?') . ' hari' : 'belum dijual (harga 0)' }}</div>@endforeach
        </div>
    </x-admin.card>
</div>
<div class="mt-6 grid gap-4 md:gap-6 xl:grid-cols-3">
    <x-admin.card title="Pengguna teratas (30 hr)">
        <div class="divide-y divide-gray-100 px-5 pb-4 dark:divide-gray-800 sm:px-6">
            @forelse ($top as $t)
                <div class="flex items-center justify-between gap-3 py-3 text-sm"><a class="min-w-0 truncate text-brand-500" href="{{ route('admin.customers.show', $t->id) }}">{{ $t->name ?: $t->email }}</a><span class="whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $t->n }} scan</span></div>
            @empty <p class="py-4 text-sm text-gray-500">Belum ada pemakaian.</p> @endforelse
        </div>
    </x-admin.card>
    <x-admin.card title="Scan terbaru" desc="Tautan dan isi audit tidak ditampilkan di sini." class="xl:col-span-2">
        <div class="px-5 pb-5 sm:px-6">
        <x-admin.table :cols="['Waktu', 'Pengguna', 'Jenis', 'Status', 'Hitung']" :empty="$recent->isEmpty() ? 'Belum ada scan.' : null">
            @foreach ($recent as $s)
                <tr>
                    <td class="py-3 pr-4 whitespace-nowrap">{{ $s->created_at->timezone(config('temanseller.display_timezone'))->format('d M H:i') }}</td>
                    <td class="py-3 pr-4"><a class="text-brand-500" href="{{ route('admin.customers.show', $s->customer_id) }}">{{ $s->customer->name ?? $s->customer->email ?? '#' . $s->customer_id }}</a></td>
                    <td class="py-3 pr-4">{{ $s->kind === 'product' ? 'Produk' : 'Toko' }}</td>
                    <td class="py-3 pr-4"><x-admin.badge :tone="$s->status === 'done' ? 'success' : (in_array($s->status, ['failed', 'expired']) ? 'error' : 'gray')">{{ $s->status }}</x-admin.badge>@if ($s->error)<span class="block text-xs text-gray-500">{{ $s->error }}</span>@endif</td>
                    <td class="py-3">{{ $s->counted ? '✓' : '—' }}</td>
                </tr>
            @endforeach
        </x-admin.table>
        </div>
    </x-admin.card>
</div>
@endsection
