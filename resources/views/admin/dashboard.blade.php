@extends('layouts.app', ['title' => 'Dashboard'])
@use('App\Support\Fmt')
@section('content')
<x-common.page-breadcrumb pageTitle="Dashboard" />

@foreach ($warnings as [$msg, $tab])
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-warning-500 bg-warning-50 p-4 text-sm text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/15 dark:text-orange-400">
        <span>⚠ {{ $msg }}</span>
        <a href="{{ route('admin.settings', $tab) }}" class="font-medium underline">Atur sekarang →</a>
    </div>
@endforeach

<div class="grid grid-cols-2 gap-4 md:gap-6 xl:grid-cols-4">
    <x-admin.stat label="Total pengguna" :value="number_format($stats['customers'])" :hint="$stats['expired'] . ' berakhir'" tone="brand" />
    <x-admin.stat label="Trial aktif" :value="number_format($stats['trial'])" tone="warning" />
    <x-admin.stat label="Bulanan aktif" :value="number_format($stats['monthly'])" tone="success" />
    <x-admin.stat label="Lifetime" :value="number_format($stats['lifetime'])" tone="brand" />
    <x-admin.stat label="Pendapatan bulan ini" :value="Fmt::rp($stats['revenue_month'])" :hint="'Total: ' . Fmt::rp($stats['revenue_total'])" tone="success" />
    <x-admin.stat label="Order menunggu" :value="number_format($stats['pending'])" tone="warning" />
    <x-admin.stat label="Analisis AI hari ini" :value="number_format($stats['ai_today'])" :hint="$stats['ai_cache_today'] . ' dari cache'" tone="brand" />
    <x-admin.stat label="AI gagal hari ini" :value="number_format($stats['ai_err_today'])" :tone="$stats['ai_err_today'] ? 'error' : 'gray'" />
</div>

<div class="mt-6 grid gap-4 md:gap-6 xl:grid-cols-2">
    <x-admin.card title="Pengguna baru" desc="30 hari terakhir"><div data-chart='@json($signupChart)'></div></x-admin.card>
    <x-admin.card title="Pendapatan" desc="30 hari terakhir (order lunas)"><div data-chart='@json($revenueChart)'></div></x-admin.card>
</div>

<div class="mt-6 grid gap-4 md:gap-6 xl:grid-cols-2">
    <x-admin.card title="Order terbaru">
        <x-slot:actions><a class="text-sm text-brand-500" href="{{ route('admin.orders') }}">Semua →</a></x-slot:actions>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($latestOrders as $o)
                <div class="flex items-center justify-between gap-3 py-3 text-sm">
                    <div class="min-w-0"><div class="truncate text-gray-800 dark:text-white/90">{{ $o->customer?->email }}</div>
                        <div class="text-xs text-gray-500">{{ $o->plan }} · {{ Fmt::dt($o->created_at) }}</div></div>
                    <div class="text-right"><div class="font-medium text-gray-800 dark:text-white/90">{{ Fmt::rp($o->amount) }}</div><x-admin.badge :tone="Fmt::orderTone($o->status)">{{ $o->status }}</x-admin.badge></div>
                </div>
            @empty <p class="py-6 text-center text-sm text-gray-500">Belum ada order.</p> @endforelse
        </div>
    </x-admin.card>
    <x-admin.card title="Pengguna terbaru">
        <x-slot:actions><a class="text-sm text-brand-500" href="{{ route('admin.customers') }}">Semua →</a></x-slot:actions>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($latestCustomers as $c)
                @php [$lbl, $tone] = Fmt::status($c->access()); @endphp
                <a href="{{ route('admin.customers.show', $c) }}" class="flex items-center justify-between gap-3 py-3 text-sm hover:opacity-80">
                    <div class="min-w-0"><div class="truncate text-gray-800 dark:text-white/90">{{ $c->name ?: $c->email }}</div><div class="truncate text-xs text-gray-500">{{ $c->email }}</div></div>
                    <x-admin.badge :tone="$tone">{{ $lbl }}</x-admin.badge>
                </a>
            @empty <p class="py-6 text-center text-sm text-gray-500">Belum ada pengguna.</p> @endforelse
        </div>
    </x-admin.card>
</div>
@endsection
