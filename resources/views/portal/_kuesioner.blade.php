@if ($qn)
<div class="mt-5 rounded-xl border border-ts-orange/40 bg-ts-cream/60 p-5 text-sm text-gray-700">
    <div class="font-semibold text-ts-navy">Langkah penting: isi kuesioner brand</div>
    <p class="mt-1">Supaya hasil konten pas dengan brand-mu, unduh kuesioner, isi seadanya (20–30 menit, boleh dilewati kalau belum tahu), lalu kirim kembali ke kami.</p>
    <ol class="mt-3 space-y-1.5">
        <li><b>1.</b> Unduh kuesioner: <a href="{{ $qn['download'] }}" class="font-semibold text-ts-orange-text underline" rel="noopener noreferrer">Unduh (.docx)</a> · <a href="{{ $qn['open'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-500 underline">buka di Google Docs ↗</a></li>
        <li><b>2.</b> Isi di Word / Google Docs.</li>
        <li><b>3.</b> Kirim kembali
            @if ($qn['email'] && $qn['wa']) lewat email ke <b>{{ $qn['email'] }}</b> atau lewat WhatsApp (tombol di bawah).
            @elseif ($qn['email']) lewat email ke <b>{{ $qn['email'] }}</b>.
            @elseif ($qn['wa']) lewat WhatsApp (tombol di bawah).
            @else sesuai arahan admin. @endif
        </li>
    </ol>
    <p class="mt-3 text-xs text-gray-500">Jangan menuliskan password, kode OTP, atau data pembayaran di kuesioner.</p>
</div>
@endif
@if ($waUrl ?? null)
<div class="mt-4 flex flex-wrap gap-3">
    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-success-600">{{ $waLabel ?? 'Tanya lewat WhatsApp' }}</a>
    @if ($qn && $qn['email'])<a href="mailto:{{ $qn['email'] }}?subject={{ rawurlencode('Kuesioner brand - ' . ($c->name ?: $c->email)) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Kirim lewat email</a>@endif
</div>
<p class="mt-2 text-xs text-gray-500">Pesan WhatsApp sudah berisi ringkasan pesananmu, jadi admin langsung paham konteksnya.</p>
@endif
