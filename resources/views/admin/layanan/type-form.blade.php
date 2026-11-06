@extends('layouts.app', ['title' => 'Jenis paket'])
@section('content')
<x-common.page-breadcrumb pageTitle="Jenis paket" />
@include('admin.layanan._tabs')
<form method="POST" action="{{ $type->exists ? route('admin.layanan.type.update', $type) : route('admin.layanan.type.store') }}">@csrf
<x-admin.card title="{{ $type->exists ? 'Ubah jenis paket' : 'Jenis paket baru' }}" class="max-w-2xl">
    <div class="grid gap-4">
        <x-admin.input label="Nama jenis" name="name" :value="$type->name" placeholder="mis. Instagram + Threads" />
        <x-admin.input label="Deskripsi singkat" name="description" :value="$type->description" />
        <x-admin.input label="Urutan tampil" name="sort" type="number" :value="$type->sort" />
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active))> Aktif (tampil di halaman publik)</label>
        <div class="flex gap-3"><x-admin.btn>Simpan</x-admin.btn><x-admin.btn tone="outline" type="button" :href="route('admin.layanan.plans')">Batal</x-admin.btn></div>
    </div>
</x-admin.card>
</form>
@endsection
