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
    <x-admin.table :cols="['Order', 'Pengguna', 'Aplikasi', 'Paket', 'Jumlah', 'Status', 'Metode', 'Dibuat', 'Dibayar']" :empty="$orders->isEmpty() ? 'Tidak ada order.' : null">
        @foreach ($orders as $o)
            <tr><td class="py-3 pr-4 font-mono text-xs">{{ $o->order_id }}</td>
                <td class="py-3 pr-4">@if($o->customer)<a class="text-brand-500" href="{{ route('admin.customers.show', $o->customer) }}">{{ $o->customer->email }}</a>@endif</td>
                <td class="py-3 pr-4">{{ $o->app ?: 'shofee' }}</td><td class="py-3 pr-4">{{ $o->plan }}</td><td class="py-3 pr-4 font-medium">{{ Fmt::rp($o->amount) }}</td>
                <td class="py-3 pr-4"><x-admin.badge :tone="Fmt::orderTone($o->status)">{{ $o->status }}</x-admin.badge></td>
                <td class="py-3 pr-4">{{ $o->payment_type ?: '—' }}</td><td class="py-3 pr-4 whitespace-nowrap">{{ Fmt::dt($o->created_at) }}</td><td class="py-3 whitespace-nowrap">{{ Fmt::dt($o->paid_at) }}</td></tr>
        @endforeach
    </x-admin.table>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-admin.card>
@endsection
