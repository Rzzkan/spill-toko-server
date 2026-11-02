@props(['label', 'value', 'hint' => null, 'tone' => 'gray'])
@php $dot = ['gray' => 'bg-gray-400', 'brand' => 'bg-brand-500', 'success' => 'bg-success-500', 'warning' => 'bg-warning-500', 'error' => 'bg-error-500'][$tone] ?? 'bg-gray-400'; @endphp
<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span class="size-2 rounded-full {{ $dot }}"></span>{{ $label }}</div>
    <div class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $value }}</div>
    @if ($hint)<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</div>@endif
</div>
