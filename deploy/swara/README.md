# Swara worker (Colab)

`worker_src.py` adalah sumber yang mudah dibaca & dipelihara untuk notebook worker Swara (FastAPI + verifikasi
token Ed25519 + tunnel Cloudflare). Ini **bukan** yang dibagikan ke pembeli lisensi.

Yang dibagikan ke pembeli adalah `Swara_Worker.ipynb` (dikirim langsung ke Owner, tidak disimpan di repo ini
karena isinya versi dipersingkat dari file ini) — bagian intinya sama persis secara perilaku, hanya nama
variabel lokal dipersingkat dan komentar dihapus, supaya tidak gampang dibaca sambil lalu. Perlindungan yang
sebenarnya tetap di server (Settings `security.swara_sign_key`, endpoint `/api/swara/pubkey`,
`App\Services\Swara`), bukan di notebook.

**Kalau logikanya diubah** (format token, aturan sid, dsb.), ubah di `worker_src.py` di sini DULU, uji dengan
starlette TestClient (stub `run_tts`, stub `requests.get` untuk pubkey), baru buat ulang versi dipersingkat
untuk notebook dan kirim ulang ke pembeli yang sudah punya lisensi.

Isi fungsi `run_tts()` memanggil OmniVoice (`k2-fsa/OmniVoice`, Apache-2.0) — ganti kalau nanti pindah mesin.
