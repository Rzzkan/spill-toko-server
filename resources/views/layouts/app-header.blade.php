<header class="sticky top-0 z-50 flex w-full shrink-0 border-gray-200 bg-white xl:border-b dark:border-gray-800 dark:bg-gray-900" x-data="{ menu: false }">
    <div class="flex grow items-center justify-between gap-2 px-3 py-3 xl:px-6 xl:py-4">
        <div class="flex items-center gap-2 sm:gap-4">
            <button type="button" aria-label="Toggle sidebar" @click.stop="$store.sidebar.toggleExpanded()"
                class="hidden size-11 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 xl:inline-flex dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">
                <x-svg.menu-two class="size-auto! rtl:-scale-x-100" />
            </button>
            <button type="button" aria-label="Toggle sidebar" @click.stop="$store.sidebar.toggleMobileOpen()"
                class="inline-flex size-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 xl:hidden dark:border-gray-800 dark:text-gray-400">
                <x-svg.x x-show="$store.sidebar.isMobileOpen" class="size-6" />
                <x-svg.menu-two x-show="!$store.sidebar.isMobileOpen" class="size-auto!" />
            </button>
            <span class="hidden text-sm text-gray-500 sm:block dark:text-gray-400">Panel admin · {{ config('app.name') }}</span>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <button type="button" aria-label="Toggle theme" @click="$store.theme.toggle()"
                class="relative inline-flex size-11 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">
                <x-svg.sun x-show="$store.theme.resolvedTheme === 'dark'" />
                <x-svg.moon x-show="$store.theme.resolvedTheme !== 'dark'" />
            </button>
            <x-header.user-dropdown />
        </div>
    </div>
</header>
