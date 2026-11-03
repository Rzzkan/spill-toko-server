{{-- Logo + nama. name = temanseller | shofee (berkas public/img/brand/<name>-180.png). text = teks di samping logo (default: nama merek). --}}
@props(['name' => 'temanseller', 'size' => 'size-10', 'text' => null, 'href' => null])
@php
    $label = $text ?? ($name === 'shofee' ? 'Shofee' : 'TemanSeller');
    $img = '<img src="' . e(asset('img/brand/' . $name . '-180.png')) . '" alt="' . e($label) . '" class="' . e($size . ($name === 'shofee' ? ' rounded-xl' : '')) . ' object-contain">';
    $attrs = $attributes->merge(['class' => 'flex items-center gap-2.5 font-bold']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attrs }}>{!! $img !!}{{ $label }}</a>
@else
    <span {{ $attrs }}>{!! $img !!}{{ $label }}</span>
@endif
