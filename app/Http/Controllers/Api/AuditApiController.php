<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\AuditScan;
use App\Services\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Endpoint untuk extension: kirim data hasil pengumpulan di browser pengguna. Autentikasi = token sekali pakai per pemindaian. */
class AuditApiController extends Controller
{
    public function data(Request $r, int $id): JsonResponse
    {
        $token = (string) $r->header('X-Audit-Token', '');
        $scan = AuditScan::query()->find($id);
        if (!$scan || $token === '') throw new ApiException('Pemindaian tidak ditemukan', 404);
        if (strlen($r->getContent()) > 400000) throw new ApiException('Data terlalu besar', 413);
        $s = Audit::receive($scan, $token, $r->json()->all());
        return response()->json(['ok' => true, 'status' => $s->status]);
    }
}
