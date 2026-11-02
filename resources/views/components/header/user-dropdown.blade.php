@php $u = auth()->user(); @endphp
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" class="flex items-center gap-2 text-gray-700 dark:text-gray-400">
        <span class="grid size-11 place-items-center rounded-lg bg-brand-50 text-base font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ strtoupper(mb_substr($u->name ?? 'A', 0, 1)) }}</span>
        <span class="hidden text-sm font-medium sm:block">{{ $u->name }}</span>
        <x-svg.chevron-down class="size-5 text-gray-500 duration-200 dark:text-gray-400" x-bind:class="open ? 'rotate-180' : ''" />
    </button>
    <div x-show="open" x-cloak x-transition class="absolute right-0 z-999 mt-3 w-64 rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <div class="px-3 py-2">
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $u->name }}</span>
            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $u->email }}</span>
        </div>
        <a href="{{ route('admin.profile') }}" class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">Profil &amp; kata sandi</a>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-error-600 hover:bg-error-50 dark:hover:bg-error-500/10">Keluar</button>
        </form>
    </div>
</div>
