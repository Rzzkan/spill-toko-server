@extends('layouts.app', ['title' => 'Detail Pengguna'])
@use('App\Support\Fmt')
@section('content')
@php $a = $c->access(); [$lbl, $tone] = Fmt::status($a); @endphp
<x-common.page-breadcrumb pageTitle="Detail Pengguna" />
<div class="grid gap-4 md:gap-6 xl:grid-cols-3">
    <x-admin.card class="xl:col-span-1">
        <div class="flex items-center gap-4">
            @if ($c->picture)<img src="{{ $c->picture }}" referrerpolicy="no-referrer" class="size-16 rounded-full" alt="">@endif
            <div class="min-w-0"><div class="truncate text-lg font-semibold text-gray-800 dark:text-white/90">{{ $c->name ?: '—' }}</div><div class="truncate text-sm text-gray-500">{{ $c->email }}</div></div>
        </div>
        <dl class="mt-5 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd><x-admin.badge :tone="$tone">{{ $lbl }}</x-admin.badge></dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Berlaku s/d</dt><dd class="text-gray-800 dark:text-white/90">{{ $a['until'] ? Fmt::dt(\Illuminate\Support\Carbon::createFromTimestamp($a['until'])) : ($c->lifetime ? 'Selamanya' : '—') }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Trial berakhir</dt><dd class="text-gray-800 dark:text-white/90">{{ Fmt::dt($c->trial_ends_at) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Langganan s/d</dt><dd class="text-gray-800 dark:text-white/90">{{ Fmt::dt($c->paid_until) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Daftar</dt><dd class="text-gray-800 dark:text-white/90">{{ Fmt::dt($c->created_at) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Login terakhir</dt><dd class="text-gray-800 dark:text-white/90">{{ Fmt::dt($c->last_login_at) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">AI hari ini</dt><dd class="text-gray-800 dark:text-white/90">{{ \App\Services\Ai::usedToday($c) }} / {{ \App\Services\Ai::limitFor($c) ?? '∞' }}</dd></div>
        </dl>
    </x-admin.card>

    <x-admin.card title="Kelola akses" desc="Semua aksi dicatat di riwayat admin." class="xl:col-span-2">
        <div class="grid gap-4 md:grid-cols-2">
            <form method="POST" action="{{ route('admin.customers.update', $c) }}" class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800">@csrf
                <div class="text-sm font-medium text-gray-800 dark:text-white/90">Tambah langganan bulanan</div>
                <input type="hidden" name="action" value="add_days">
                <x-admin.input name="days" type="number" value="30" min="1" max="3650" help="Hari ditambahkan dari tanggal berakhir (atau hari ini bila sudah lewat)." />
                <x-admin.btn tone="success" size="sm">Tambah hari</x-admin.btn>
            </form>
            <form method="POST" action="{{ route('admin.customers.update', $c) }}" class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800">@csrf
                <div class="text-sm font-medium text-gray-800 dark:text-white/90">Atur ulang trial</div>
                <input type="hidden" name="action" value="add_trial">
                <x-admin.input name="days" type="number" value="7" min="1" max="3650" help="Trial berakhir N hari dari sekarang." />
                <x-admin.btn tone="outline" size="sm">Atur trial</x-admin.btn>
            </form>
        </div>
        <div class="mt-4 flex flex-wrap gap-3">
            @if (!$c->lifetime)
                <form method="POST" action="{{ route('admin.customers.update', $c) }}" data-confirm="Beri akses Lifetime?">@csrf<input type="hidden" name="action" value="lifetime"><x-admin.btn size="sm">Jadikan Lifetime</x-admin.btn></form>
            @else
                <form method="POST" action="{{ route('admin.customers.update', $c) }}" data-confirm="Cabut Lifetime?">@csrf<input type="hidden" name="action" value="unlifetime"><x-admin.btn tone="outline" size="sm">Cabut Lifetime</x-admin.btn></form>
            @endif
            <form method="POST" action="{{ route('admin.customers.update', $c) }}" data-confirm="Cabut semua akses pengguna ini sekarang?">@csrf<input type="hidden" name="action" value="revoke"><x-admin.btn tone="danger" size="sm">Cabut akses</x-admin.btn></form>
        </div>
    </x-admin.card>
</div>

<div class="mt-6 grid gap-4 md:gap-6 xl:grid-cols-2">
    <x-admin.card title="Order">
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($orders as $o)
                <div class="flex items-center justify-between gap-3 py-3 text-sm">
                    <div><div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $o->order_id }}</div><div class="text-xs text-gray-500">{{ $o->plan }} · {{ Fmt::dt($o->created_at) }}</div></div>
                    <div class="text-right"><div class="font-medium text-gray-800 dark:text-white/90">{{ Fmt::rp($o->amount) }}</div><x-admin.badge :tone="Fmt::orderTone($o->status)">{{ $o->status }}</x-admin.badge></div>
                </div>
            @empty <p class="py-4 text-sm text-gray-500">Belum ada order.</p> @endforelse
        </div>
    </x-admin.card>
    <x-admin.card title="Riwayat admin">
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($logs as $l)
                <div class="py-3 text-sm"><div class="text-gray-800 dark:text-white/90">{{ $l->note }}</div><div class="text-xs text-gray-500">{{ $l->admin?->name }} · {{ Fmt::dt($l->created_at) }}</div></div>
            @empty <p class="py-4 text-sm text-gray-500">Belum ada tindakan admin.</p> @endforelse
        </div>
    </x-admin.card>
</div>
<x-admin.card title="Pemakaian AI terbaru" class="mt-6">
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead><tr class="text-xs uppercase text-gray-500"><th class="py-2 pr-4">Waktu</th><th class="py-2 pr-4">Jenis</th><th class="py-2 pr-4">Sumber</th><th class="py-2 pr-4">Model</th><th class="py-2 pr-4">Token</th><th class="py-2">Status</th></tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
        @forelse ($aiLogs as $l)
            <tr><td class="py-2 pr-4 whitespace-nowrap">{{ Fmt::dt($l->created_at) }}</td><td class="py-2 pr-4">{{ $l->kind }}</td><td class="py-2 pr-4">{{ $l->cache_hit ? 'cache' : 'AI' }}</td><td class="py-2 pr-4">{{ $l->model }}</td><td class="py-2 pr-4">{{ $l->tokens_in }}/{{ $l->tokens_out }}</td><td class="py-2"><x-admin.badge :tone="$l->status === 'ok' ? 'success' : 'error'">{{ $l->status }}</x-admin.badge></td></tr>
        @empty <tr><td colspan="6" class="py-4 text-gray-500">Belum ada.</td></tr> @endforelse
        </tbody></table></div>
</x-admin.card>
@endsection
