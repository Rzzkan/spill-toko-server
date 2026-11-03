@extends('layouts.app', ['title' => 'Pengaturan'])
@section('content')
<x-common.page-breadcrumb pageTitle="Pengaturan" />

<div class="mb-5 flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-800">
    @foreach ($groups as $k => $l)
        <a href="{{ route('admin.settings', $k) }}" class="-mb-px border-b-2 px-4 py-3 text-sm font-medium {{ $k === $group ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">{{ $l }}</a>
    @endforeach
</div>

{{-- Panduan per tab --}}
@if ($group === 'google')
<x-admin.card title="Cara menyiapkan Google Login" class="mb-6">
    <ol class="list-decimal space-y-1.5 pl-5 text-sm text-gray-600 dark:text-gray-400">
        <li>Buka <b>Google Cloud Console → APIs &amp; Services → Credentials → Create credentials → OAuth client ID</b>, tipe <b>Web application</b>.</li>
        <li>Pada <b>Authorized redirect URIs</b>, tambahkan: <code>{{ $redirect }}</code></li>
        <li>Pada <b>Authorized JavaScript origins</b>, tambahkan <b>dua</b> alamat (tanpa garis miring di akhir): <code>{{ $origin }}</code> (login di halaman web <code>/login</code>) dan <code>{{ rtrim($redirect, '/') }}</code> (login dari extension). Jika yang kedua tidak ada, Google menampilkan <i>Error 401: invalid_client — no registered origin</i>.</li>
        <li>Salin <b>Client ID</b> ke kolom di bawah lalu simpan. Pastikan OAuth consent screen berstatus <i>In production</i> agar semua akun Google bisa masuk.</li>
    </ol>
    <div class="mt-4 flex flex-wrap items-center gap-2"><code id="redir" class="break-all rounded-lg bg-gray-100 px-3 py-2 text-xs dark:bg-white/10 dark:text-gray-200">{{ $redirect }}</code><x-admin.btn tone="outline" size="sm" type="button" data-copy-target="#redir">Salin</x-admin.btn></div>
    <p class="mt-2 text-xs text-gray-500">Client ID yang sama juga harus dipasang di <code>manifest.json</code> / <code>src/config.js</code> extension (lihat tab Extension).</p>
</x-admin.card>
@elseif ($group === 'midtrans')
<x-admin.card title="Cara menyiapkan Midtrans" class="mb-6">
    <ol class="list-decimal space-y-1.5 pl-5 text-sm text-gray-600 dark:text-gray-400">
        <li>Di dashboard Midtrans → <b>Settings → Access Keys</b>, salin Server Key sesuai mode (Sandbox / Production).</li>
        <li>Di <b>Settings → Configuration</b>, isi <b>Payment Notification URL</b> dengan URL berikut, dan <b>Finish Redirect URL</b> dengan URL kedua.</li>
    </ol>
    <div class="mt-4 space-y-2 text-xs">
        <div class="flex flex-wrap items-center gap-2"><span class="w-28 text-gray-500">Notification URL</span><code id="wh" class="break-all rounded-lg bg-gray-100 px-3 py-2 dark:bg-white/10 dark:text-gray-200">{{ $webhook }}</code><x-admin.btn tone="outline" size="sm" type="button" data-copy-target="#wh">Salin</x-admin.btn></div>
        <div class="flex flex-wrap items-center gap-2"><span class="w-28 text-gray-500">Finish URL</span><code id="fin" class="break-all rounded-lg bg-gray-100 px-3 py-2 dark:bg-white/10 dark:text-gray-200">{{ $finish }}</code><x-admin.btn tone="outline" size="sm" type="button" data-copy-target="#fin">Salin</x-admin.btn></div>
    </div>
</x-admin.card>
@elseif ($group === 'affiliate')
<x-admin.card title="Cara menyiapkan Affiliate Shopee" class="mb-6">
    <ol class="list-decimal space-y-1.5 pl-5 text-sm text-gray-600 dark:text-gray-400">
        <li>Ajukan akses di <b>Shopee Affiliate → Open API</b> (akun affiliate Anda). Setelah disetujui akan terbit <b>App ID</b> dan <b>Secret</b>.</li>
        <li>Isi keduanya di bawah, nyalakan <b>Aktifkan data Affiliate</b>, simpan, lalu klik <b>Tes Affiliate</b>. Pesan tes menampilkan contoh data mentah.</li>
        <li>Tab <b>Affiliate</b> di extension akan menampilkan komisi per produk (Xtra, Shopee, total) setelah ini aktif. Data video & live tidak tersedia di Open API.</li>
    </ol>
</x-admin.card>
@elseif ($group === 'prompt')
<div class="mb-6 rounded-xl border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">Prompt bawaan sudah terisi. Ubah sesuai kebutuhan; mengosongkan kolom mengembalikan ke bawaan. <b>Jangan ubah nama kunci JSON</b> di skema keluaran (ringkasan, skor, label, temuan, peluang, risiko, rekomendasi, kata_kunci, saran_judul) karena dipakai tampilan extension. Hasil cache lama otomatis tidak terpakai saat prompt berubah.</div>
@endif

@if ($group === 'ekstensi')
<x-admin.card title="Nilai untuk dipasang di extension" desc="Salin ke src/config.js lalu build ulang extension." class="mb-6">
    <div class="space-y-4 text-sm">
        @foreach ([['API_BASE', $ext['api_base'], 'ab'], ['LICENSE_PUBKEY (base64)', $ext['pubkey'], 'pk']] as [$l, $v, $id])
            <div><div class="mb-1 text-gray-500">{{ $l }}</div>
                <div class="flex flex-wrap items-center gap-2"><code id="{{ $id }}" class="break-all rounded-lg bg-gray-100 px-3 py-2 text-xs dark:bg-white/10 dark:text-gray-200">{{ $v }}</code><x-admin.btn tone="outline" size="sm" type="button" data-copy-target="#{{ $id }}">Salin</x-admin.btn></div></div>
        @endforeach
        <div><div class="mb-1 text-gray-500">Origin server (host permission di manifest)</div><code class="rounded-lg bg-gray-100 px-3 py-2 text-xs dark:bg-white/10 dark:text-gray-200">{{ $origin }}/*</code></div>
    </div>
</x-admin.card>
<x-admin.card title="Ganti kunci lisensi" desc="Berbahaya: semua lisensi yang sudah terbit tidak valid sampai extension dirilis ulang dengan kunci publik baru." class="mb-6 border-error-200 dark:border-error-500/30">
    <form method="POST" action="{{ route('admin.settings.regen') }}" class="flex flex-wrap items-end gap-3">@csrf
        <div class="w-64"><x-admin.input name="confirm" placeholder="Ketik GANTI KUNCI" autocomplete="off" /></div>
        <x-admin.btn tone="danger" size="sm">Buat kunci baru</x-admin.btn>
    </form>
</x-admin.card>
@endif

<form method="POST" action="{{ route('admin.settings.update', $group) }}"
    x-data="{
        res: {}, busy: { google: false, midtrans: false, affiliate: false, ai: false, models: false }, models: [],
        async run(what) {
            this.busy[what] = true; this.res[what] = null;
            try {
                const r = await fetch('/admin/pengaturan-tes/' + what, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                let j; try { j = await r.json(); } catch (_) { j = { ok: false, message: 'Server membalas HTTP ' + r.status + (r.status === 419 ? ' (sesi kedaluwarsa — muat ulang halaman lalu coba lagi)' : '') + '.' }; }
                this.res[what] = j;
                if (j.models) this.models = j.models;
            } catch (e) { this.res[what] = { ok: false, message: 'Gagal menghubungi server admin: ' + e.message }; }
            this.busy[what] = false;
        }
    }">
    @csrf
    <x-admin.card :title="$groups[$group]" desc="Disimpan ke database; rahasia dienkripsi.">
        <div class="space-y-6">
            @foreach ($fields as $f)
                @php $name = 's[' . str_replace('.', '__', $f['key']) . ']'; $id = 'f-' . str_replace('.', '_', $f['key']); $old = old('s.' . str_replace('.', '__', $f['key']), $f['value']); @endphp
                <div class="grid gap-2 lg:grid-cols-3 lg:gap-6">
                    <div><label for="{{ $id }}" class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $f['label'] }}</label>
                        @if (!empty($f['help']))<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $f['help'] }}</p>@endif</div>
                    <div class="lg:col-span-2">
                        @switch($f['type'])
                            @case('bool')
                                <input type="hidden" name="{{ $name }}" value="0">
                                <label class="inline-flex cursor-pointer items-center gap-3"><input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked(old('s.' . str_replace('.', '__', $f['key']), \App\Services\Settings::get($f['key'])) == '1')>
                                    <span class="relative h-6 w-11 rounded-full bg-gray-200 transition peer-checked:bg-brand-500 after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5 dark:bg-white/10"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Aktif</span></label>
                                @break
                            @case('secret')
                                <div class="flex flex-wrap items-center gap-3">
                                    <input id="{{ $id }}" type="password" name="{{ $name }}" autocomplete="new-password" placeholder="{{ $f['is_set'] ? '•••••••• (tersimpan — kosongkan untuk tetap)' : 'Belum diisi' }}"
                                        class="h-11 min-w-0 flex-1 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-dark-900 dark:text-white/90">
                                    @if ($f['is_set'])<label class="flex items-center gap-2 text-xs text-gray-500"><input type="checkbox" name="clear[{{ str_replace('.', '__', $f['key']) }}]" value="1"> Hapus</label>@endif
                                </div>
                                @break
                            @case('textarea')
                                <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $f['group'] === 'prompt' ? 12 : 4 }}" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 font-mono text-xs text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-dark-900 dark:text-white/90">{{ $old }}</textarea>
                                @break
                            @case('model')
                                <input id="{{ $id }}" list="models-list" type="text" name="{{ $name }}" value="{{ $old }}" autocomplete="off" placeholder="mis. deepseek-v4-flash"
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-dark-900 dark:text-white/90">
                                @break
                            @default
                                <input id="{{ $id }}" type="{{ in_array($f['type'], ['number']) ? 'number' : 'text' }}" name="{{ $name }}" value="{{ $old }}" @if (isset($f['min'])) min="{{ $f['min'] }}" @endif
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-dark-900 dark:text-white/90">
                        @endswitch
                    </div>
                </div>
            @endforeach
            <datalist id="models-list"><template x-for="m in models" :key="m"><option :value="m"></option></template></datalist>
        </div>

        <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">
            <x-admin.btn>Simpan pengaturan</x-admin.btn>
            @if (in_array($group, ['google', 'midtrans', 'affiliate', 'ai']))
                <span class="text-xs text-gray-500">Tes memakai nilai yang <b>sudah tersimpan</b>:</span>
                @if ($group === 'google')<x-admin.btn tone="outline" type="button" @click="run('google')" x-bind:disabled="busy.google === true"><span x-text="busy.google ? 'Menguji…' : 'Tes Google'"></span></x-admin.btn>@endif
                @if ($group === 'midtrans')<x-admin.btn tone="outline" type="button" @click="run('midtrans')" x-bind:disabled="busy.midtrans === true"><span x-text="busy.midtrans ? 'Menguji…' : 'Tes Midtrans'"></span></x-admin.btn>@endif
                @if ($group === 'affiliate')<x-admin.btn tone="outline" type="button" @click="run('affiliate')" x-bind:disabled="busy.affiliate === true"><span x-text="busy.affiliate ? 'Menguji…' : 'Tes Affiliate'"></span></x-admin.btn>@endif
                @if ($group === 'ai')
                    <x-admin.btn tone="outline" type="button" @click="run('ai')" x-bind:disabled="busy.ai === true"><span x-text="busy.ai ? 'Menguji…' : 'Tes koneksi kenari.id'"></span></x-admin.btn>
                    <x-admin.btn tone="outline" type="button" @click="run('models')" x-bind:disabled="busy.models === true"><span x-text="busy.models ? 'Memuat…' : 'Muat daftar model'"></span></x-admin.btn>
                @endif
            @endif
        </div>
        <template x-for="(r, k) in res" :key="k"><div x-show="r" class="mt-4 rounded-lg px-4 py-3 text-sm" :class="r && r.ok ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500' : 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-500'" x-text="r ? (r.ok ? '✓ ' : '✗ ') + r.message : ''"></div></template>
    </x-admin.card>
</form>
@endsection
