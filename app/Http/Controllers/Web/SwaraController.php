<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Plans;
use App\Services\Settings;
use App\Services\Swara;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Swara (cloning suara): halaman publik penjelasan + harga, dan halaman alat (wajib login + lisensi).
 * Audio TIDAK pernah melalui server ini — lihat App\Services\Swara.
 */
class SwaraController extends Controller
{
    public function landing()
    {
        return view('public.swara', [
            'plans' => Plans::all(Swara::APP),
            'loggedIn' => request()->session()->has('customer_id'),
        ]);
    }

    public function page(Request $r)
    {
        $c = $r->attributes->get('customer');
        $a = $c->access(Swara::APP);
        if (!$a['access']) {
            return redirect('/swara')->with('msg', 'Swara belum dibeli atau lisensi sudah berakhir. Beli lisensi dulu untuk membuka alat ini.');
        }
        return view('portal.swara', ['c' => $c, 'plans' => Plans::all(Swara::APP), 'ttl' => Swara::TOKEN_TTL]);
    }

    /** Panduan penggunaan (mirip modul "wajib dibaca"), untuk pelanggan yang sudah punya lisensi. */
    public function guide(Request $r)
    {
        $c = $r->attributes->get('customer');
        if (!$c->access(Swara::APP)['access']) {
            return redirect('/swara')->with('msg', 'Swara belum dibeli atau lisensi sudah berakhir. Beli lisensi dulu untuk membuka panduan ini.');
        }
        return view('portal.swara-panduan', [
            'videos' => ['akses' => Settings::raw('swara.video_akses'), 'sesi' => Settings::raw('swara.video_sesi'), 'clone' => Settings::raw('swara.video_clone')],
        ]);
    }

    public function pair(Request $r): JsonResponse
    {
        $r->validate(['sid' => 'required|string|max:64']);
        Swara::pair($r->attributes->get('customer'), (string) $r->input('sid'));
        return response()->json(['ok' => true]);
    }

    public function token(Request $r): JsonResponse
    {
        $r->validate(['sid' => 'required|string|max:64']);
        return response()->json(Swara::issueToken($r->attributes->get('customer'), (string) $r->input('sid')));
    }
}
