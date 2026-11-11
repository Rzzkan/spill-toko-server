{{-- Kartu video tutorial (mirip modul panduan berisi video YouTube). $url kosong = video belum diisi Admin. --}}
<div class="mt-3">
    @if ($url)
        <a href="{{ $url }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl border-2 border-emerald-200 bg-white p-3 hover:border-ts-orange">
            <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-ts-navy text-white">▶</span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-ts-navy">{{ $title }}</span>
                <span class="block truncate text-xs text-gray-500">{{ $desc }}</span>
            </span>
        </a>
    @else
        <div class="flex items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-3 text-gray-400">
            <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-gray-200 text-gray-400">▶</span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold">{{ $title }}</span>
                <span class="block truncate text-xs">Video segera hadir</span>
            </span>
        </div>
    @endif
</div>
