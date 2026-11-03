@props(['app', 'size' => 'size-11'])
@php $slug = is_object($app) ? $app->slug : (string) $app; $emoji = is_object($app) ? ($app->icon ?: '📦') : '📦'; $f = 'img/brand/' . $slug . '-64.png'; @endphp
@if (is_file(public_path($f)))
    <img src="{{ asset('img/brand/' . $slug . '-180.png') }}" alt="" {{ $attributes->merge(['class' => $size . ' rounded-xl object-contain']) }}>
@else
    <span {{ $attributes->merge(['class' => $size . ' grid place-items-center rounded-xl bg-brand-50 text-xl']) }}>{{ $emoji }}</span>
@endif
