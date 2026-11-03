@extends('layouts.app', ['title' => 'Aplikasi'])
@use('App\Support\Fmt')
@section('content')
<x-common.page-breadcrumb pageTitle="Aplikasi" />
@if (session('ok'))<div class="mb-4 rounded-lg bg-success-50 p-3 text-sm text-success-700">{{ session('ok') }}</div>@endif
@if (session('error'))<div class="mb-4 rounded-lg bg-error-50 p-3 text-sm text-error-700">{{ session('error') }}</div>@endif
<x-admin.card title="Aplikasi TemanSeller" desc="Setiap aplikasi punya trial, paket, dan langganan sendiri. Pengguna melihat semua langganannya di halaman akun.">
    <x-slot:actions><x-admin.btn size="sm" :href="route('admin.apps.create')">+ Aplikasi baru</x-admin.btn></x-slot:actions>
    <div class="overflow-x-auto px-5 pb-5 sm:px-6">
        <table class="w-full text-left text-sm">
            <thead><tr class="border-b border-gray-100 text-xs uppercase text-gray-500 dark:border-gray-800"><th class="py-3 pr-4">Aplikasi</th><th class="py-3 pr-4">Kode</th><th class="py-3 pr-4">Trial</th><th class="py-3 pr-4">Paket</th><th class="py-3 pr-4">Pengguna</th><th class="py-3 pr-4">Status</th><th class="py-3"></th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($apps as $a)
                @php $pl = \App\Services\Plans::all($a->slug); @endphp
                <tr class="text-gray-700 dark:text-gray-300">
                    <td class="py-3 pr-4"><div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-brand-50 text-lg dark:bg-white/10">{{ $a->icon ?: '📦' }}</span>
                        <span><span class="block font-medium text-gray-800 dark:text-white/90">{{ $a->name }}</span><span class="text-xs text-gray-500">{{ $a->tagline }}</span></span></div></td>
                    <td class="py-3 pr-4 font-mono text-xs">{{ $a->slug }}</td>
                    <td class="py-3 pr-4">{{ \App\Services\Plans::trialDays($a->slug) }} hari</td>
                    <td class="py-3 pr-4 text-xs">@forelse ($pl as $k => $p){{ $p['label'] }} {{ Fmt::rp($p['price']) }}@if (!$loop->last) · @endif @empty <span class="text-gray-400">belum ada</span> @endforelse</td>
                    <td class="py-3 pr-4">{{ number_format($subs[$a->slug] ?? 0) }}</td>
                    <td class="py-3 pr-4"><x-admin.badge :tone="$a->active ? 'success' : 'gray'">{{ $a->active ? 'Aktif' : 'Nonaktif' }}</x-admin.badge></td>
                    <td class="py-3 text-right"><a class="text-brand-500" href="{{ route('admin.apps.edit', $a) }}">Ubah</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-admin.card>
@endsection
