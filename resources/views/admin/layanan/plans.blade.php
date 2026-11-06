@extends('layouts.app', ['title' => 'Layanan Sosmed'])
@use('App\Support\Fmt')
@section('content')
<x-common.page-breadcrumb pageTitle="Layanan Sosmed" />
@include('admin.layanan._tabs')
<div class="mb-4 flex flex-wrap gap-2">
    <x-admin.btn size="sm" :href="route('admin.layanan.plan.create')">+ Paket baru</x-admin.btn>
    <x-admin.btn size="sm" tone="outline" :href="route('admin.layanan.type.create')">+ Jenis paket baru</x-admin.btn>
</div>
<p class="mb-4 text-sm text-gray-500">Paket tampil di halaman publik hanya bila <b>aktif</b>, harga &gt; 0, dan jenisnya aktif. Paket awal berupa draf nonaktif — isi harga lalu aktifkan.</p>
@foreach ($types as $t)
<x-admin.card :title="$t->name" :desc="$t->description" class="mb-6">
    <x-slot:actions>
        <div class="flex items-center gap-2">
            <x-admin.badge :tone="$t->is_active ? 'success' : 'gray'">{{ $t->is_active ? 'Aktif' : 'Nonaktif' }}</x-admin.badge>
            <x-admin.btn size="sm" tone="outline" :href="route('admin.layanan.type.edit', $t)">Ubah jenis</x-admin.btn>
            <form method="POST" action="{{ route('admin.layanan.type.delete', $t) }}" onsubmit="return confirm('Hapus jenis paket ini?')">@csrf<x-admin.btn size="sm" tone="outline">Hapus</x-admin.btn></form>
        </div>
    </x-slot:actions>
    <div class="px-5 pb-5 sm:px-6">
        <x-admin.table :cols="['Paket', 'Harga', 'Lama', 'Status', '']" :empty="$t->plans->isEmpty() ? 'Belum ada paket di jenis ini.' : null">
            @foreach ($t->plans as $p)
                <tr>
                    <td class="py-3 pr-4"><span class="font-medium text-gray-800 dark:text-white/90">{{ $p->name }}</span>@if ($p->is_featured) <x-admin.badge tone="brand">Unggulan</x-admin.badge>@endif<span class="block text-xs text-gray-500">{{ $p->tagline }}</span></td>
                    <td class="py-3 pr-4">{{ $p->price > 0 ? Fmt::rp($p->price) . ' ' . $p->period : '—' }}</td>
                    <td class="py-3 pr-4">{{ $p->duration_days }} hari</td>
                    <td class="py-3 pr-4"><x-admin.badge :tone="$p->is_active ? 'success' : 'gray'">{{ $p->is_active ? 'Aktif' : 'Draf' }}</x-admin.badge></td>
                    <td class="py-3 text-right whitespace-nowrap"><a class="text-brand-500" href="{{ route('admin.layanan.plan.edit', $p) }}">Ubah</a>
                        <form method="POST" action="{{ route('admin.layanan.plan.delete', $p) }}" class="ml-3 inline" onsubmit="return confirm('Hapus paket ini? Riwayat langganan tetap tersimpan.')">@csrf<button class="text-error-500">Hapus</button></form></td>
                </tr>
            @endforeach
        </x-admin.table>
    </div>
</x-admin.card>
@endforeach
@endsection
