<div class="mb-5 flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-800">
    @foreach (['plans' => ['Paket & Harga', 'admin/layanan/paket*'], 'portfolio' => ['Portofolio', 'admin/layanan/portofolio*'], 'subs' => ['Langganan Pelanggan', 'admin/layanan/langganan*']] as $k => [$l, $pat])
        <a href="{{ route('admin.layanan.' . $k) }}" class="-mb-px border-b-2 px-4 py-3 text-sm font-medium {{ request()->is($pat) || ($k === 'plans' && request()->is('admin/layanan/jenis*')) ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">{{ $l }}</a>
    @endforeach
    <a href="{{ route('admin.settings', 'layanan') }}" class="-mb-px border-b-2 border-transparent px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Pengaturan (WhatsApp &amp; pembayaran)</a>
</div>
@if (session('ok'))<div class="mb-4 rounded-lg bg-success-50 p-3 text-sm text-success-700">{{ session('ok') }}</div>@endif
@if (session('error'))<div class="mb-4 rounded-lg bg-error-50 p-3 text-sm text-error-700">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="mb-4 rounded-lg bg-error-50 p-3 text-sm text-error-700">{{ $errors->first() }}</div>@endif
