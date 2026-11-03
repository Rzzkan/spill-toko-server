@extends('layouts.app', ['title' => $isNew ? 'Aplikasi baru' : 'Ubah aplikasi'])
@section('content')
<x-common.page-breadcrumb :pageTitle="$isNew ? 'Aplikasi baru' : 'Ubah ' . $app->name" />
@php $p = (array) ($app->plans ?? []); $isMain = $app->slug === \App\Models\App::DEFAULT; @endphp
@if (session('error'))<div class="mb-4 rounded-lg bg-error-50 p-3 text-sm text-error-700">{{ session('error') }}</div>@endif
<form method="POST" action="{{ $isNew ? route('admin.apps.store') : route('admin.apps.update', $app) }}" class="space-y-6">@csrf
    <x-admin.card title="Identitas" class="max-w-3xl">
        <div class="grid gap-4 px-5 pb-5 sm:grid-cols-2 sm:px-6">
            @if ($isNew)<x-admin.input label="Kode (huruf kecil, unik)" name="slug" help="Dipakai di alamat dan oleh aplikasinya (header X-App). Tidak bisa diubah." placeholder="mis. kasirku" />@else
                <div><div class="mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-400">Kode</div><div class="h-11 rounded-lg border border-gray-200 px-4 py-3 font-mono text-sm dark:border-gray-800 dark:text-white/90">{{ $app->slug }}</div></div>@endif
            <x-admin.input label="Nama aplikasi" name="name" :value="$app->name" />
            <x-admin.input label="Deskripsi singkat" name="tagline" :value="$app->tagline" class="sm:col-span-2" />
            <x-admin.input label="URL aplikasi (tujuan tombol di landing)" name="url" :value="$app->url" class="sm:col-span-2" placeholder="{{ $isMain ? 'https://temanseller.com/shofee' : 'https://temanseller.com/kasirku' }}" help="Alamat penuh (https://…) atau path seperti /shofee. Dipakai tombol \"Lihat detail\" di landing TemanSeller. Kosong = halaman bawaan." />
            <x-admin.input label="Ikon (emoji)" name="icon" :value="$app->icon" />
            <x-admin.input label="Urutan tampil" name="sort" type="number" :value="$app->sort" />
            <x-admin.input label="Trial (hari)" name="trial_days" type="number" :value="$app->trial_days" help="Berlaku untuk pengguna baru aplikasi ini. 0 = tanpa trial." />
            <label class="flex items-center gap-2 pt-7 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="active" value="1" @checked(old('active', $app->active))> Aktif (tampil di akun pengguna)</label>
        </div>
    </x-admin.card>
    <x-admin.card title="Paket & harga" desc="Harga tiap aplikasi diatur terpisah di sini. Harga 0 = paket itu tidak dijual; harga coret hanya tampilan." class="max-w-3xl">
        <div class="grid gap-4 px-5 pb-5 sm:grid-cols-2 sm:px-6">
            <x-admin.input label="Bulanan – harga (Rp)" name="monthly_price" type="number" :value="data_get($p, 'monthly.price', 0)" help="Harga yang benar-benar ditagih." />
            <x-admin.input label="Bulanan – harga coret (Rp)" name="monthly_original" type="number" :value="data_get($p, 'monthly.original', 0)" />
            <x-admin.input label="Bulanan – masa aktif (hari)" name="monthly_days" type="number" :value="data_get($p, 'monthly.days', 30)" />
            <div class="hidden sm:block"></div>
            <x-admin.input label="Tahunan – harga (Rp)" name="yearly_price" type="number" :value="data_get($p, 'yearly.price', 0)" />
            <x-admin.input label="Tahunan – harga coret (Rp)" name="yearly_original" type="number" :value="data_get($p, 'yearly.original', 0)" />
            <x-admin.input label="Tahunan – masa aktif (hari)" name="yearly_days" type="number" :value="data_get($p, 'yearly.days', 365)" />
            <div class="hidden sm:block"></div>
            <x-admin.input label="Lifetime – harga (Rp)" name="lifetime_price" type="number" :value="data_get($p, 'lifetime.price', 0)" />
            <label class="flex items-center gap-2 pt-7 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="lifetime_enabled" value="1" @checked(old('lifetime_enabled', data_get($p, 'lifetime.enabled', false)))> Jual paket Lifetime</label>
        </div>
    </x-admin.card>
    <div class="flex gap-3"><x-admin.btn>Simpan</x-admin.btn><x-admin.btn tone="outline" type="button" :href="route('admin.apps')">Batal</x-admin.btn></div>
</form>
@endsection
