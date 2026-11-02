@extends('layouts.app', ['title' => 'Pemakaian AI'])
@use('App\Support\Fmt')
@section('content')
<x-common.page-breadcrumb pageTitle="Pemakaian AI" />
<div class="grid grid-cols-2 gap-4 md:gap-6 xl:grid-cols-4">
    <x-admin.stat label="Analisis baru (30 hr)" :value="number_format($totals['calls'])" :hint="'rata-rata ' . $totals['avg_ms'] . ' ms'" tone="brand" />
    <x-admin.stat label="Dari cache (30 hr)" :value="number_format($totals['cache'])" :hint="$totals['cache_rows'] . ' entri tersimpan'" tone="success" />
    <x-admin.stat label="Gagal (30 hr)" :value="number_format($totals['errors'])" :tone="$totals['errors'] ? 'error' : 'gray'" />
    <x-admin.stat label="Token (30 hr)" :value="number_format($totals['tokens_in'] + $totals['tokens_out'])" :hint="number_format($totals['tokens_in']) . ' masuk · ' . number_format($totals['tokens_out']) . ' keluar'" tone="warning" />
</div>
<div class="mt-6 grid gap-4 md:gap-6 xl:grid-cols-3">
    <x-admin.card title="Per hari" desc="30 hari terakhir" class="xl:col-span-2"><div data-chart='@json($dailyChart)'></div></x-admin.card>
    <x-admin.card title="Per jenis halaman"><div data-chart='@json($kindChart)'></div></x-admin.card>
</div>
<div class="mt-6 grid gap-4 md:gap-6 xl:grid-cols-3">
    <x-admin.card title="Pengguna teratas (30 hr)">
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($top as $t)
                <div class="flex items-center justify-between gap-3 py-3 text-sm"><a class="min-w-0 truncate text-brand-500" href="{{ route('admin.customers.show', $t->customer_id) }}">{{ $custs[$t->customer_id]->email ?? '#' . $t->customer_id }}</a>
                    <span class="whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $t->c }}× · {{ number_format($t->ti + $t->to) }} tok</span></div>
            @empty <p class="py-4 text-sm text-gray-500">Belum ada pemakaian.</p> @endforelse
        </div>
        <form method="POST" action="{{ route('admin.ai.clear') }}" data-confirm="Kosongkan semua cache hasil AI? Analisis berikutnya akan memakai kuota & token lagi." class="mt-4">@csrf
            <x-admin.btn tone="outline" size="sm">Kosongkan cache AI</x-admin.btn></form>
    </x-admin.card>
    <x-admin.card title="Log terbaru" desc="Pesan galat teknis (saldo/key) hanya tampil di sini, bukan ke pengguna." class="xl:col-span-2">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead><tr class="text-xs uppercase text-gray-500"><th class="py-2 pr-3">Waktu</th><th class="py-2 pr-3">Pengguna</th><th class="py-2 pr-3">Jenis</th><th class="py-2 pr-3">Sumber</th><th class="py-2 pr-3">ms</th><th class="py-2">Status</th></tr></thead>
            <tbody class="divide-y divide-gray-100 text-gray-700 dark:divide-gray-800 dark:text-gray-300">
            @forelse ($logs as $l)
                <tr><td class="py-2 pr-3 whitespace-nowrap">{{ Fmt::dt($l->created_at) }}</td><td class="max-w-40 truncate py-2 pr-3">{{ $l->customer?->email }}</td><td class="py-2 pr-3">{{ $l->kind }}</td><td class="py-2 pr-3">{{ $l->cache_hit ? 'cache' : $l->model }}</td><td class="py-2 pr-3">{{ $l->ms }}</td>
                    <td class="py-2">@if ($l->status === 'ok')<x-admin.badge tone="success">ok</x-admin.badge>@else<x-admin.badge tone="error" title="{{ $l->error }}">{{ \Illuminate\Support\Str::limit($l->error ?: 'error', 40) }}</x-admin.badge>@endif</td></tr>
            @empty <tr><td colspan="6" class="py-4 text-gray-500">Belum ada log.</td></tr> @endforelse
            </tbody></table></div>
    </x-admin.card>
</div>
@endsection
