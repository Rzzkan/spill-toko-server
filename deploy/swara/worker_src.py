"""
Swara worker — jalan di Google Colab milik pengguna sendiri, bukan di server TemanSeller.

Yang dikerjakan:
  1. Mengambil kunci publik Swara dari https://temanseller.com/api/swara/pubkey (aman, bukan rahasia).
  2. Membuat sid (kode sesi) acak, lalu mencetak link untuk ditempel di temanseller.com/akun/swara.
  3. Membuka tunnel (Cloudflare quick tunnel) supaya browser bisa memanggil server kecil ini langsung.
  4. Menyediakan endpoint /generate yang HANYA mau dipakai bila membawa token sah dari server TemanSeller
     (ditandatangani Ed25519, berumur maksimal 15 menit, dan sid di token harus sama dengan sid sesi ini).
  5. Audio referensi & naskah dari browser diproses di sini (GPU Colab-mu), hasilnya dikirim balik langsung
     ke browser. Tidak ada audio yang dikirim ke server TemanSeller.

Pasang OmniVoice sendiri (lihat run_tts di bawah) — bagian ini contoh standar HTTP/verifikasi, tidak eksklusif
Swara: bisa dibaca & diubah bebas, karena yang benar-benar menjaga lisensi ada di server TemanSeller, bukan di sini.
"""

import base64
import json
import secrets
import time

import requests
from fastapi import FastAPI, File, Form, HTTPException, Request, UploadFile
from fastapi.responses import JSONResponse, Response
from nacl.exceptions import BadSignatureError
from nacl.signing import VerifyKey

API_BASE = "https://temanseller.com"
ALLOWED_ORIGIN = "https://temanseller.com"
SID = secrets.token_hex(8)

_pubkey_cache: VerifyKey | None = None


def fetch_pubkey() -> VerifyKey:
    """Kunci publik di-cache, tapi diambil ulang kalau verifikasi gagal (jaga-jaga kunci diputar server)."""
    global _pubkey_cache
    if _pubkey_cache is None:
        r = requests.get(f"{API_BASE}/api/swara/pubkey", timeout=10)
        r.raise_for_status()
        _pubkey_cache = VerifyKey(base64.b64decode(r.json()["pubkey"]))
    return _pubkey_cache


def verify_token(authorization: str | None) -> dict:
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(401, "Token tidak ada")
    raw = authorization.removeprefix("Bearer ").strip()
    try:
        payload_b64, sig_b64 = raw.split(".", 1)
        payload = base64.b64decode(payload_b64)
        sig = base64.b64decode(sig_b64)
    except Exception:
        raise HTTPException(401, "Format token tidak valid")

    try:
        fetch_pubkey().verify(payload, sig)
    except BadSignatureError:
        global _pubkey_cache
        _pubkey_cache = None   # mungkin kunci baru; coba sekali lagi dari awal
        try:
            fetch_pubkey().verify(payload, sig)
        except BadSignatureError:
            raise HTTPException(401, "Tanda tangan token tidak valid")

    claim = json.loads(payload)
    if claim.get("exp", 0) < time.time():
        raise HTTPException(401, "Token sudah lewat waktu, tunggu browser memperpanjang")
    if claim.get("sid") != SID:
        raise HTTPException(401, "Sesi ini sudah digantikan sesi lain")
    return claim


def run_tts(text: str, ref_audio_path: str) -> bytes:
    """Ganti isi fungsi ini dengan pemanggilan OmniVoice (atau mesin lain) milikmu. Harus mengembalikan bytes WAV."""
    from omnivoice import OmniVoice   # pip install omnivoice (lihat panduan OmniVoice di Hugging Face k2-fsa/OmniVoice)

    model = run_tts.__dict__.setdefault("_model", OmniVoice.from_pretrained("k2-fsa/OmniVoice"))
    wav = model.generate(text=text, ref_audio=ref_audio_path)
    return wav


app = FastAPI()


@app.middleware("http")
async def cors_and_origin_guard(request: Request, call_next):
    origin = request.headers.get("origin")
    if request.method == "OPTIONS":
        resp = Response(status_code=204)
    else:
        resp = await call_next(request)
    if origin == ALLOWED_ORIGIN:
        resp.headers["Access-Control-Allow-Origin"] = ALLOWED_ORIGIN
        resp.headers["Access-Control-Allow-Headers"] = "authorization,content-type"
        resp.headers["Access-Control-Allow-Methods"] = "POST,GET,OPTIONS"
    return resp


@app.get("/health")
def health():
    return {"ok": True, "sid": SID}


@app.post("/generate")
async def generate(request: Request, ref: UploadFile = File(...), text: str = Form(...)):
    verify_token(request.headers.get("authorization"))
    if not text.strip():
        raise HTTPException(422, "Naskah kosong")
    import tempfile, os
    with tempfile.NamedTemporaryFile(suffix=".audio", delete=False) as f:
        f.write(await ref.read())
        ref_path = f.name
    try:
        wav = run_tts(text.strip()[:2000], ref_path)
    finally:
        os.unlink(ref_path)   # referensi & berkas sementara dibuang, tidak disimpan
    return Response(content=wav, media_type="audio/wav")


def open_tunnel(port: int) -> str:
    """Cloudflare quick tunnel: gratis, tanpa akun. URL-nya acak dan hanya hidup selama proses ini berjalan."""
    import os
    import re
    import subprocess

    if not os.path.exists("./cloudflared"):
        subprocess.run(["wget", "-q", "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64", "-O", "cloudflared"], check=True)
        subprocess.run(["chmod", "+x", "cloudflared"], check=True)
    proc = subprocess.Popen(["./cloudflared", "tunnel", "--url", f"http://localhost:{port}"], stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)
    deadline = time.time() + 30
    while time.time() < deadline:
        line = proc.stdout.readline()
        m = re.search(r"https://[-\w]+\.trycloudflare\.com", line)
        if m:
            return m.group(0)
    raise RuntimeError("Tunnel tidak terbentuk dalam 30 detik, coba jalankan ulang sel ini")


def start(port: int = 8000):
    import threading

    import uvicorn

    threading.Thread(target=lambda: uvicorn.run(app, host="0.0.0.0", port=port, log_level="warning"), daemon=True).start()
    time.sleep(2)
    public_url = open_tunnel(port)
    u = base64.b64encode(public_url.encode()).decode()
    link = f"{API_BASE}/akun/swara#sid={SID}&u={u}"
    print("Notebook siap. Tempel link ini di halaman Swara (temanseller.com/akun/swara):\n")
    print(link)
    return link
