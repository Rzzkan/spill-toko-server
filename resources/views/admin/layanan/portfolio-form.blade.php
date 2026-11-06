@extends('layouts.app', ['title' => 'Portofolio'])
@section('content')
<x-common.page-breadcrumb pageTitle="Portofolio" />
@include('admin.layanan._tabs')
<form method="POST" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.layanan.portfolio.update', $item) : route('admin.layanan.portfolio.store') }}">@csrf
<x-admin.card title="{{ $item->exists ? 'Ubah portofolio' : 'Portofolio baru' }}" class="max-w-3xl">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-admin.input label="Judul" name="title" :value="$item->title" class="sm:col-span-2" placeholder="mis. Rebranding feed toko hijab" />
        <x-admin.input label="Nama klien / brand" name="client_name" :value="$item->client_name" />
        <x-admin.input label="Platform" name="platform" :value="$item->platform" placeholder="Instagram / Threads / Instagram + Threads" />
        <x-admin.input label="Hasil (opsional)" name="result_text" :value="$item->result_text" class="sm:col-span-2" placeholder="mis. Followers +2.4rb dalam 3 bulan" />
        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Deskripsi</label>
            <textarea name="description" rows="4" class="w-full rounded-lg border border-gray-300 bg-transparent p-3 text-sm dark:border-gray-700 dark:text-white/90">{{ old('description', $item->description) }}</textarea></div>
        <x-admin.input label="Tautan akun / hasil (https)" name="link_url" :value="$item->link_url" class="sm:col-span-2" />
        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Gambar (JPG/PNG/WebP, maks 3 MB)</label>
            @if ($item->imageUrl())<img src="{{ $item->imageUrl() }}" alt="" class="mb-2 h-32 rounded-lg object-cover"><label class="mb-2 flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="remove_image" value="1"> Hapus gambar saat ini</label>@endif
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm"></div>
        <x-admin.input label="Urutan tampil" name="sort" type="number" :value="$item->sort" />
        <label class="flex items-center gap-2 pt-7 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Tampilkan di halaman publik</label>
        <div class="flex gap-3 sm:col-span-2"><x-admin.btn>Simpan</x-admin.btn><x-admin.btn tone="outline" type="button" :href="route('admin.layanan.portfolio')">Batal</x-admin.btn></div>
    </div>
</x-admin.card>
</form>
@endsection
