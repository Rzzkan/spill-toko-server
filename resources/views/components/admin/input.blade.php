@props(['label' => null, 'name', 'type' => 'text', 'help' => null, 'value' => null])
<div>
    @if ($label)<label for="f-{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}</label>@endif
    <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old(str_replace(['[', ']'], ['.', ''], $name), $value) }}"
        {{ $attributes->merge(['class' => 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-dark-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800']) }}>
    @error($name)<p class="mt-1 text-xs text-error-600">{{ $message }}</p>@enderror
    @if ($help)<p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $help }}</p>@endif
</div>
