@extends('layouts.app', ['title' => 'Order'])
@use('App\Support\Fmt')
@section('content')
<x-common.page-breadcrumb pageTitle="Order" />
<x-admin.card>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-gray-500">Total pendapatan lunas: <b class="text-gray-800 dark:text-white/90">{{ Fmt::rp($total) }}</b></div>
        <form method="GET" class="flex flex-wrap gap-2">
            <select name="status" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua status</option>
                @foreach (['paid', 'pending', 'expired', 'cancelled', 'denied', 'failed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
            <input name="q" value="{{ request('q') }}" placeholder="Order ID / email…" class="h-10 w-52 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white/90">
            <x-admin.btn size="sm">Filter</x-admin.btn>
        </form>
    </div>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead><tr class="border-b border-gray-100 text-xs uppercase text-gray-500 dark:border-gray-800"><th class="py-3 pr-4">Order</th><th class="py-3 pr-4">Pengguna</th><th class="py-3 pr-4">Paket</th><th class="py-3 pr-4">Jumlah</th><th class="py-3 pr-4">Status</th><th class="py-3 pr-4">Metode</th><th class="py-3 pr-4">Dibuat</th><th class="py-3">Dibayar</th></tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
        @forelse ($orders as $o)
            <tr><td class="py-3 pr-4 font-mono text-xs">{{ $o->order_id }}</td>
                <td class="py-3 pr-4">@if($o->customer)<a class="text-brand-500" href="{{ route('admin.customers.show', $o->customer) }}">{{ $o->customer->email }}</a>@endif</td>
                <td class="py-3 pr-4">{{ $o->plan }}</td><td class="py-3 pr-4 font-medium">{{ Fmt::rp($o->amount) }}</td>
                <td class="py-3 pr-4"><x-admin.badge :tone="Fmt::orderTone($o->status)">{{ $o->status }}</x-admin.badge></td>
                <td class="py-3 pr-4">{{ $o->payment_type ?: '—' }}</td><td class="py-3 pr-4 whitespace-nowrap">{{ Fmt::dt($o->created_at) }}</td><td class="py-3 whitespace-nowrap">{{ Fmt::dt($o->paid_at) }}</td></tr>
        @empty <tr><td colspan="8" class="py-10 text-center text-gray-500">Tidak ada order.</td></tr> @endforelse
        </tbody></table></div>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-admin.card>
@endsection
