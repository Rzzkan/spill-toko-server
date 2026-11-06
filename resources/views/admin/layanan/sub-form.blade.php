@extends('layouts.app', ['title' => 'Langganan layanan'])
@section('content')
<x-common.page-breadcrumb pageTitle="Langganan Layanan" />
@include('admin.layanan._tabs')
@php $f = fn ($d) => $d ? $d->format('Y-m-d') : null; @endphp
<form method="POST" action="{{ $sub->exists ? route('admin.layanan.sub.update', $sub) : route('admin.layanan.sub.store') }}">@csrf
<x-admin.card title="{{ $sub->exists ? 'Kelola langganan #' . $sub->id : 'Tambah langganan manual' }}" class="max-w-3xl">
    <div class="grid gap-4 sm:grid-cols-2">
        @if ($sub->exists)
            <div class="sm:col-span-2 text-sm text-gray-600 dark:text-gray-400">Pelanggan: <b>{{ $sub->customer?->name }}</b> ({{ $sub->customer?->email }})@if ($sub->customer_note)<br>Catatan pelanggan: “{{ $sub->customer_note }}”@endif</div>
        @else
            <x-admin.input label="Email pelanggan (sudah pernah login)" name="email" type="email" class="sm:col-span-2" />
        @endif
        <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Paket</label>
            <select name="service_plan_id" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white/90">
                <option value="">— kustom / tetap —</option>
                @foreach ($plans as $p)<option value="{{ $p->id }}" @selected((int) old('service_plan_id', $sub->service_plan_id) === $p->id)>{{ $p->type->name }} – {{ $p->name }}</option>@endforeach
            </select></div>
        <x-admin.input label="Nama paket (salinan)" name="plan_name" :value="$sub->plan_name" help="Kosong = ikut nama paket terpilih." />
        <x-admin.input label="Harga (Rp)" name="price" type="number" :value="$sub->price" />
        <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status</label>
            <select name="status" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white/90">
                @foreach (\App\Models\ServiceSubscription::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $sub->status) === $k)>{{ $l }}</option>@endforeach
            </select></div>
        @php $chip = 'rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-brand-500 hover:text-brand-600 dark:border-gray-700 dark:text-gray-300'; @endphp
        <div>
            <x-admin.input label="Mulai" name="starts_at" type="date" :value="$f($sub->starts_at)" />
            <div class="mt-2 flex flex-wrap gap-2"><button type="button" data-start-today class="{{ $chip }}">Hari ini</button></div>
        </div>
        <div>
            <x-admin.input label="Berakhir" name="ends_at" type="date" :value="$f($sub->ends_at)" help="Kosong + status Aktif = otomatis sesuai lama paket." />
            <div class="mt-2 flex flex-wrap items-center gap-2"><span class="text-xs text-gray-500">Durasi dari tanggal Mulai:</span>
                @foreach ([1, 3, 6, 12] as $m)<button type="button" data-add-months="{{ $m }}" class="{{ $chip }}">{{ $m }} Bulan</button>@endforeach
                <button type="button" data-clear-end class="text-xs text-gray-500 underline">Kosongkan</button></div>
        </div>
        <x-admin.input label="URL folder Drive (https)" name="drive_url" :value="$sub->drive_url" class="sm:col-span-2" />
        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Pesan untuk pelanggan (tampil di akun mereka)</label>
            <textarea name="admin_note" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent p-3 text-sm dark:border-gray-700 dark:text-white/90">{{ old('admin_note', $sub->admin_note) }}</textarea></div>
        <div class="flex gap-3 sm:col-span-2"><x-admin.btn>Simpan</x-admin.btn><x-admin.btn tone="outline" type="button" :href="route('admin.layanan.subs')">Batal</x-admin.btn></div>
    </div>
</x-admin.card>
</form>
<script>
(() => {
    const s = document.querySelector('[name=starts_at]'), e = document.querySelector('[name=ends_at]');
    const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const today = () => fmt(new Date());
    const addMonths = (iso, n) => { const [y, m, d] = iso.split('-').map(Number); const t = new Date(y, m - 1 + n, 1); const last = new Date(t.getFullYear(), t.getMonth() + 1, 0).getDate(); t.setDate(Math.min(d, last)); return fmt(t); };
    document.querySelector('[data-start-today]')?.addEventListener('click', () => { s.value = today(); });
    document.querySelectorAll('[data-add-months]').forEach((b) => b.addEventListener('click', () => { if (!s.value) s.value = today(); e.value = addMonths(s.value, +b.dataset.addMonths); }));
    document.querySelector('[data-clear-end]')?.addEventListener('click', () => { e.value = ''; });
})();
</script>
@endsection
