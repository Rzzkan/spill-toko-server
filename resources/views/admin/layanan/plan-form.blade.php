@extends('layouts.app', ['title' => 'Paket layanan'])
@section('content')
<x-common.page-breadcrumb pageTitle="Paket layanan" />
@include('admin.layanan._tabs')
<form method="POST" action="{{ $plan->exists ? route('admin.layanan.plan.update', $plan) : route('admin.layanan.plan.store') }}">@csrf
<x-admin.card title="{{ $plan->exists ? 'Ubah paket' : 'Paket baru' }}" class="max-w-3xl">
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jenis paket</label>
            <select name="service_type_id" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white/90">
                @foreach ($types as $t)<option value="{{ $t->id }}" @selected((int) old('service_type_id', $plan->service_type_id) === $t->id)>{{ $t->name }}</option>@endforeach
            </select></div>
        <x-admin.input label="Nama paket" name="name" :value="$plan->name" placeholder="mis. Growth" />
        <x-admin.input label="Tagline" name="tagline" :value="$plan->tagline" class="sm:col-span-2" />
        <x-admin.input label="Harga (Rp)" name="price" type="number" :value="$plan->price" help="0 = belum dijual." />
        <x-admin.input label="Teks periode" name="period" :value="$plan->period" help="Tampil setelah harga, mis. /bulan." />
        <x-admin.input label="Lama langganan (hari)" name="duration_days" type="number" :value="$plan->duration_days" help="Dipakai saat paket diaktifkan tanpa tanggal berakhir." />
        <x-admin.input label="Label (opsional)" name="badge" :value="$plan->badge" placeholder="mis. Paling laris" />
        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Fitur (satu per baris)</label>
            <textarea name="features" rows="6" class="w-full rounded-lg border border-gray-300 bg-transparent p-3 text-sm dark:border-gray-700 dark:text-white/90">{{ old('features', $plan->features) }}</textarea></div>
        <x-admin.input label="Urutan tampil" name="sort" type="number" :value="$plan->sort" />
        <div class="flex flex-col gap-2 pt-1 text-sm text-gray-700 dark:text-gray-300">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))> Aktif (dijual di halaman publik)</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $plan->is_featured))> Tandai sebagai unggulan</label>
        </div>
        <div class="flex gap-3 sm:col-span-2"><x-admin.btn>Simpan</x-admin.btn><x-admin.btn tone="outline" type="button" :href="route('admin.layanan.plans')">Batal</x-admin.btn></div>
    </div>
</x-admin.card>
</form>
@endsection
