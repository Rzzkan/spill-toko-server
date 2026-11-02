@props(['tone' => 'primary', 'href' => null, 'type' => 'submit', 'size' => 'md'])
@php
    $t = [
        'primary' => 'bg-brand-500 text-white hover:bg-brand-600 shadow-theme-xs',
        'outline' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]',
        'danger' => 'bg-error-500 text-white hover:bg-error-600 shadow-theme-xs',
        'success' => 'bg-success-500 text-white hover:bg-success-600 shadow-theme-xs',
    ][$tone] ?? '';
    $s = $size === 'sm' ? 'px-3 py-2 text-xs' : 'px-4 py-3 text-sm';
    $cls = "inline-flex items-center justify-center gap-2 rounded-lg font-medium transition disabled:opacity-50 $s $t";
@endphp
@if ($href)<a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else<button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>@endif
