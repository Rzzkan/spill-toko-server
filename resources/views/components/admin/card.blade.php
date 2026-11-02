@props(['title' => null, 'desc' => null])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    @if ($title)
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6 sm:py-5">
            <div>
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">{{ $title }}</h3>
                @if ($desc)<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $desc }}</p>@endif
            </div>
            @isset($actions){{ $actions }}@endisset
        </div>
        <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">{{ $slot }}</div>
    @else
        <div class="p-5 sm:p-6">{{ $slot }}</div>
    @endif
</div>
