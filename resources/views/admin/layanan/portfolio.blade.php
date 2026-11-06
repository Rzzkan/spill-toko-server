@extends('layouts.app', ['title' => 'Portofolio layanan'])
@section('content')
<x-common.page-breadcrumb pageTitle="Portofolio" />
@include('admin.layanan._tabs')
<x-admin.card title="Portofolio" desc="Tampil di halaman /layanan. Tambah atau hapus kapan saja.">
    <x-slot:actions><x-admin.btn size="sm" :href="route('admin.layanan.portfolio.create')">+ Tambah portofolio</x-admin.btn></x-slot:actions>
    <div class="px-5 pb-5 sm:px-6">
        <x-admin.table :cols="['Gambar', 'Judul', 'Klien', 'Platform', 'Status', '']" :empty="$items->isEmpty() ? 'Belum ada portofolio.' : null">
            @foreach ($items as $it)
                <tr>
                    <td class="py-3 pr-4">@if ($it->imageUrl())<img src="{{ $it->imageUrl() }}" alt="" class="size-14 rounded-lg object-cover">@else<span class="grid size-14 place-items-center rounded-lg bg-gray-100 dark:bg-white/5">📸</span>@endif</td>
                    <td class="py-3 pr-4"><span class="font-medium text-gray-800 dark:text-white/90">{{ $it->title }}</span><span class="block text-xs text-gray-500">{{ $it->result_text }}</span></td>
                    <td class="py-3 pr-4">{{ $it->client_name ?: '—' }}</td>
                    <td class="py-3 pr-4">{{ $it->platform ?: '—' }}</td>
                    <td class="py-3 pr-4"><x-admin.badge :tone="$it->is_active ? 'success' : 'gray'">{{ $it->is_active ? 'Tampil' : 'Disembunyikan' }}</x-admin.badge></td>
                    <td class="py-3 text-right whitespace-nowrap"><a class="text-brand-500" href="{{ route('admin.layanan.portfolio.edit', $it) }}">Ubah</a>
                        <form method="POST" action="{{ route('admin.layanan.portfolio.delete', $it) }}" class="ml-3 inline" onsubmit="return confirm('Hapus portofolio ini?')">@csrf<button class="text-error-500">Hapus</button></form></td>
                </tr>
            @endforeach
        </x-admin.table>
    </div>
</x-admin.card>
@endsection
