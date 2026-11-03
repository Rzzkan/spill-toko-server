@extends('layouts.app', ['title' => 'Pengguna'])
@use('App\Support\Fmt')
@section('content')
<x-common.page-breadcrumb pageTitle="Pengguna" />
<x-admin.card>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            @foreach (['' => 'Semua', 'trial' => 'Trial', 'monthly' => 'Bulanan', 'lifetime' => 'Lifetime', 'expired' => 'Berakhir'] as $k => $l)
                <a href="{{ route('admin.customers', array_filter(['app' => $app, 'status' => $k, 'q' => request('q')])) }}"
                    class="rounded-lg px-3 py-2 text-sm font-medium {{ (string) request('status') === (string) $k ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">{{ $l }} <span class="text-xs opacity-70">{{ $counts[$k] }}</span></a>
            @endforeach
        </div>
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <select name="app" onchange="this.form.submit()" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white/90">
                @foreach ($apps as $ap)<option value="{{ $ap->slug }}" @selected($ap->slug === $app)>{{ $ap->name }}</option>@endforeach
            </select>
            <input name="q" value="{{ request('q') }}" placeholder="Cari email / nama…" class="h-10 w-56 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white/90">
            <x-admin.btn size="sm">Cari</x-admin.btn>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead><tr class="border-b border-gray-100 text-xs uppercase text-gray-500 dark:border-gray-800">
                <th class="py-3 pr-4">Pengguna</th><th class="py-3 pr-4">Status ({{ $apps->firstWhere('slug', $app)?->name }})</th><th class="py-3 pr-4">Berlaku s/d</th><th class="py-3 pr-4">Order</th><th class="py-3 pr-4">Daftar</th><th class="py-3 pr-4">Login terakhir</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($customers as $c)
                @php $a = $c->access($app); [$lbl, $tone] = Fmt::status($a); @endphp
                <tr class="text-gray-700 dark:text-gray-300">
                    <td class="py-3 pr-4"><a href="{{ route('admin.customers.show', $c) }}" class="flex items-center gap-3">
                        @if ($c->picture)<img src="{{ $c->picture }}" referrerpolicy="no-referrer" class="size-9 rounded-full" alt="">@else<span class="grid size-9 place-items-center rounded-full bg-gray-100 text-xs dark:bg-white/10">{{ strtoupper(mb_substr($c->email, 0, 1)) }}</span>@endif
                        <span><span class="block font-medium text-gray-800 dark:text-white/90">{{ $c->name ?: '—' }}</span><span class="text-xs text-gray-500">{{ $c->email }}</span></span></a></td>
                    <td class="py-3 pr-4"><x-admin.badge :tone="$tone">{{ $lbl }}</x-admin.badge></td>
                    <td class="py-3 pr-4 whitespace-nowrap">{{ $a['until'] ? Fmt::dt(\Illuminate\Support\Carbon::createFromTimestamp($a['until']), false) : ($a['plan'] === 'lifetime' ? 'Selamanya' : '—') }}</td>
                    <td class="py-3 pr-4">{{ $c->orders_count }}</td>
                    <td class="py-3 pr-4 whitespace-nowrap">{{ Fmt::dt($c->created_at, false) }}</td>
                    <td class="py-3 pr-4 whitespace-nowrap">{{ Fmt::dt($c->last_login_at) }}</td>
                </tr>
            @empty <tr><td colspan="6" class="py-10 text-center text-gray-500">Tidak ada pengguna.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $customers->links() }}</div>
</x-admin.card>
@endsection
