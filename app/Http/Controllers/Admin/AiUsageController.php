<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\AiCache;
use App\Models\AiLog;
use App\Models\Customer;
use Illuminate\Http\Request;

class AiUsageController extends Controller
{
    public function index()
    {
        $tz = config('temanseller.display_timezone');
        $days = 30;
        $since = now($tz)->subDays($days - 1)->startOfDay()->utc();
        [$labels, $fresh] = DashboardController::dayBuckets($days);
        $cache = $fresh;
        $err = $fresh;
        foreach (AiLog::query()->where('created_at', '>=', $since)->get(['created_at', 'cache_hit', 'status']) as $l) {
            $k = $l->created_at->copy()->setTimezone($tz)->format('Y-m-d');
            if (!isset($fresh[$k])) continue;
            if ($l->status === 'error') $err[$k]++;
            elseif ($l->cache_hit) $cache[$k]++;
            else $fresh[$k]++;
        }
        $base = AiLog::query()->where('created_at', '>=', $since);
        $kinds = (clone $base)->where('cache_hit', false)->where('status', 'ok')->selectRaw('kind, count(*) c')->groupBy('kind')->pluck('c', 'kind');
        $top = (clone $base)->where('cache_hit', false)->where('status', 'ok')->selectRaw('customer_id, count(*) c, sum(tokens_in) ti, sum(tokens_out) `to`')
            ->groupBy('customer_id')->orderByDesc('c')->limit(10)->get();
        $custs = Customer::query()->whereIn('id', $top->pluck('customer_id'))->get()->keyBy('id');
        return view('admin.ai', [
            'totals' => [
                'calls' => (clone $base)->where('cache_hit', false)->where('status', 'ok')->count(),
                'cache' => (clone $base)->where('cache_hit', true)->count(),
                'errors' => (clone $base)->where('status', 'error')->count(),
                'tokens_in' => (int) (clone $base)->sum('tokens_in'),
                'tokens_out' => (int) (clone $base)->sum('tokens_out'),
                'avg_ms' => (int) (clone $base)->where('cache_hit', false)->where('status', 'ok')->avg('ms'),
                'cache_rows' => AiCache::count(),
            ],
            'dailyChart' => ['type' => 'bar', 'categories' => $labels, 'colors' => ['#465fff', '#12b76a', '#f04438'],
                'series' => [['name' => 'Analisis baru', 'data' => array_values($fresh)], ['name' => 'Dari cache', 'data' => array_values($cache)], ['name' => 'Gagal', 'data' => array_values($err)]]],
            'kindChart' => ['type' => 'donut', 'height' => 260, 'labels' => ['Produk', 'Toko', 'Pencarian'],
                'series' => [(int) ($kinds['product'] ?? 0), (int) ($kinds['shop'] ?? 0), (int) ($kinds['search'] ?? 0)]],
            'top' => $top, 'custs' => $custs,
            'logs' => AiLog::query()->with('customer')->latest('created_at')->limit(40)->get(),
        ]);
    }

    public function clearCache(Request $r)
    {
        $n = AiCache::query()->delete();
        AdminLog::record('ai_cache_clear', "$n baris cache dihapus");
        return back()->with('ok', "Cache AI dikosongkan ($n entri).");
    }
}
