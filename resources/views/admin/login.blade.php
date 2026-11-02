@extends('layouts.fullscreen-layout', ['title' => 'Masuk'])
@section('content')
<div class="relative z-1 flex min-h-screen items-center justify-center bg-white p-6 sm:p-0 dark:bg-gray-900">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <span class="mx-auto mb-4 grid size-14 place-items-center rounded-2xl bg-brand-500 text-2xl text-white">🛍️</span>
            <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">{{ config('app.name') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masuk ke panel admin</p>
        </div>
        <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-5 rounded-2xl border border-gray-200 p-6 dark:border-gray-800">
            @csrf
            <x-admin.input label="Username atau email" name="login" autocomplete="username" required autofocus />
            <x-admin.input label="Kata sandi" name="password" type="password" autocomplete="current-password" required />
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"><input type="checkbox" name="remember" value="1" class="size-4 rounded border-gray-300"> Ingat saya</label>
            <x-admin.btn class="w-full">Masuk</x-admin.btn>
        </form>
    </div>
</div>
@endsection
