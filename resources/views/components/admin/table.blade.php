{{-- Tabel admin seragam. :cols = judul kolom; :empty = pesan bila tidak ada baris (tampil menggantikan isi). Isi = <tr> baris. --}}
@props(['cols' => [], 'empty' => null])
<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead><tr class="border-b border-gray-100 text-xs uppercase text-gray-500 dark:border-gray-800">
            @foreach ($cols as $c)<th class="py-3 {{ $loop->last ? '' : 'pr-4' }}">{{ $c }}</th>@endforeach
        </tr></thead>
        <tbody {{ $attributes->merge(['class' => 'divide-y divide-gray-100 text-gray-700 dark:divide-gray-800 dark:text-gray-300']) }}>
            @if ($empty !== null)
                <tr><td colspan="{{ max(1, count($cols)) }}" class="py-10 text-center text-gray-500">{{ $empty }}</td></tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>
