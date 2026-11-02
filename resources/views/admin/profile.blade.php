@extends('layouts.app', ['title' => 'Profil Admin'])
@section('content')
<x-common.page-breadcrumb pageTitle="Profil Admin" />
<x-admin.card title="Akun admin" desc="Kosongkan kata sandi bila tidak ingin mengubahnya." class="max-w-2xl">
    <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-5">@csrf
        <x-admin.input label="Nama" name="name" :value="auth()->user()->name" required />
        <x-admin.input label="Email" name="email" type="email" :value="auth()->user()->email" required />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input label="Kata sandi saat ini" name="current_password" type="password" autocomplete="current-password" />
            <div></div>
            <x-admin.input label="Kata sandi baru" name="password" type="password" autocomplete="new-password" help="Minimal 8 karakter." />
            <x-admin.input label="Ulangi kata sandi baru" name="password_confirmation" type="password" autocomplete="new-password" />
        </div>
        <x-admin.btn>Simpan</x-admin.btn>
    </form>
</x-admin.card>
@endsection
