@if ($formUrl)
<div class="mt-5 rounded-xl border border-ts-orange/40 bg-ts-cream/60 p-5 text-sm text-gray-700">
    <div class="font-semibold text-ts-navy">Langkah penting: isi kuesioner brand</div>
    <p class="mt-1">Supaya hasil konten pas dengan brand-mu, isi kuesioner singkat (20–30 menit, boleh dilewati kalau belum tahu). Jawabanmu langsung masuk ke tim kami.</p>
    <a href="{{ $formUrl }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block rounded-lg bg-ts-orange px-5 py-2.5 text-sm font-semibold text-ts-navy hover:bg-ts-orange-dark">Isi kuesioner (Google Form) ↗</a>
    <p class="mt-3 text-xs text-gray-500">Jangan menuliskan password, kode OTP, atau data pembayaran di kuesioner.</p>
</div>
@endif
@if ($waUrl ?? null)
<div class="mt-4 flex flex-wrap gap-3">
    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-success-600">{{ $waLabel ?? 'Tanya lewat WhatsApp' }}</a>
</div>
<p class="mt-2 text-xs text-gray-500">Pesan WhatsApp sudah berisi ringkasan pesananmu, jadi admin langsung paham konteksnya.</p>
@endif
