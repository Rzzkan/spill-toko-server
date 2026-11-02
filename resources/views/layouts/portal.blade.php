<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ ($title ?? 'Akun') . ' · ' . config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">
<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-lg font-semibold"><span class="grid size-9 place-items-center rounded-xl bg-brand-500 text-white">🛍️</span>{{ config('app.name') }}</a>
        @yield('nav')
    </div>
</header>
<main class="mx-auto max-w-4xl px-4 py-8">
    @if (session('error'))<div class="mb-5 rounded-xl border border-error-500 bg-error-50 p-4 text-sm text-error-700">⚠ {{ session('error') }}</div>@endif
    @if (session('ok'))<div class="mb-5 rounded-xl border border-success-500 bg-success-50 p-4 text-sm text-success-700">✓ {{ session('ok') }}</div>@endif
    @yield('content')
    <p class="py-6 text-center text-xs text-gray-400">{{ config('app.name') }} · versi {{ config('spill.version') }}</p>
</main>
@stack('scripts')
</body>
</html>
