@extends('layouts.app', ['title' => 'Langganan layanan'])
@use('App\Support\Fmt')
@section('content')
<x-common.page-breadcrumb pageTitle="Langganan Layanan" />
@include('admin.layanan._tabs')
<x-admin.card title="Langganan pelanggan" desc="Pesanan masuk berstatus Menunggu konfirmasi. Ubah ke Aktif setelah pembayaran diterima dan isi URL folder Drive.">
    <x-slot:actions><div class="flex gap-2">
        <x-admin.btn size="sm" :href="route('admin.layanan.sub.create')">+ Tambah manual</x-admin.btn></div></x-slot:actions>
    <div class="px-5 pb-5 sm:px-6">
        <div class="mb-3 flex flex-wrap gap-2 text-xs">
            <a href="{{ route('admin.layanan.subs') }}" class="rounded-full px-3 py-1 {{ !$status ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600' }}">Semua</a>
            @foreach (\App\Models\ServiceSubscription::STATUSES as $k => $l)<a href="{{ route('admin.layanan.subs', ['status' => $k]) }}" class="rounded-full px-3 py-1 {{ $status === $k ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $l }}</a>@endforeach
        </div>
        <x-admin.table :cols="['Pelanggan', 'Paket', 'Harga', 'Berlaku sampai', 'Drive', 'Status', '']" :empty="$subs->isEmpty() ? 'Belum ada langganan layanan.' : null">
            @foreach ($subs as $s)
                <tr>
                    <td class="py-3 pr-4"><span class="font-medium text-gray-800 dark:text-white/90">{{ $s->customer?->name ?: '—' }}</span><span class="block text-xs text-gray-500">{{ $s->customer?->email }}</span></td>
                    <td class="py-3 pr-4">{{ $s->plan_name }}@if ($s->customer_note)<span class="block max-w-xs truncate text-xs text-gray-500" title="{{ $s->customer_note }}">“{{ $s->customer_note }}”</span>@endif</td>
                    <td class="py-3 pr-4">{{ Fmt::rp($s->price) }}</td>
                    <td class="py-3 pr-4">{{ $s->ends_at ? Fmt::dt($s->ends_at, false) : '—' }}</td>
                    <td class="py-3 pr-4">{{ $s->drive_url ? '✓' : '—' }}</td>
                    <td class="py-3 pr-4"><x-admin.badge :tone="$s->tone()">{{ $s->label() }}</x-admin.badge></td>
                    <td class="py-3 text-right"><a class="text-brand-500" href="{{ route('admin.layanan.sub.edit', $s) }}">Kelola</a></td>
                </tr>
            @endforeach
        </x-admin.table>
        <div class="mt-4">{{ $subs->links() }}</div>
    </div>
</x-admin.card>
@endsection
