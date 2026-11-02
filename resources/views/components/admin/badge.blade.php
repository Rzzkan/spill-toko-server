@props(['tone' => 'gray'])
@php
    $c = [
        'gray' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300',
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
        'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
        'error' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
    ][$tone] ?? '';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap $c"]) }}>{{ $slot }}</span>
