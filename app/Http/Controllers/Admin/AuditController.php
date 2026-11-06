<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App as AppModel;
use App\Models\AuditScan;
use App\Models\Subscription;
use App\Services\Audit;
use App\Services\Plans;
use Illuminate\Support\Facades\DB;

/** Ringkasan Marketplace Audit: pemakaian, pelanggan berlangganan, dan pintasan ke pengaturan jatah/harga. Tautan & isi audit sengaja tidak ditampilkan. */
class AuditController extends Controller
{
    public function index()
    {
        $since = now()->subDays(30);
        $rows = AuditScan::query()->where('created_at', '>=', $since)->get(['status', 'counted', 'kind', 'created_at', 'customer_id']);
        $byDay = [];
        for ($i = 29; $i >= 0; $i--) $byDay[now()->subDays($i)->format('d/m')] = 0;
        foreach ($rows as $s) { $k = $s->created_at->format('d/m'); if (isset($byDay[$k]) && $s->counted) $byDay[$k]++; }
        $app = AppModel::bySlug(Audit::APP);
        return view('admin.audit.index', [
            'enabled' => Audit::enabled(), 'free' => Audit::freeScans(), 'cap' => Audit::paidDailyCap(), 'hours' => Audit::retentionHours(),
            'totals' => [
                'all' => $rows->count(), 'done' => $rows->where('status', 'done')->count(), 'failed' => $rows->whereIn('status', ['failed', 'expired'])->count(),
                'login' => $rows->where('status', 'needs_login')->count(), 'users' => $rows->pluck('customer_id')->unique()->count(),
                'product' => $rows->where('kind', 'product')->count(), 'shop' => $rows->where('kind', 'shop')->count(),
            ],
            'subs' => Subscription::statusCounts(Audit::APP), 'app' => $app, 'plans' => Plans::all(Audit::APP),
            'chart' => ['type' => 'bar', 'categories' => array_keys($byDay), 'series' => [['name' => 'Scan', 'data' => array_values($byDay)]]],
            'top' => DB::table('audit_scans')->join('customers', 'customers.id', '=', 'audit_scans.customer_id')->where('audit_scans.counted', true)->where('audit_scans.created_at', '>=', $since)
                ->groupBy('customers.id', 'customers.name', 'customers.email')->orderByRaw('COUNT(*) DESC')->limit(10)->get(['customers.id', 'customers.name', 'customers.email', DB::raw('COUNT(*) as n')]),
            'recent' => AuditScan::query()->with('customer:id,name,email')->latest('id')->limit(25)->get(['id', 'customer_id', 'kind', 'status', 'counted', 'error', 'created_at', 'purged_at']),
        ]);
    }
}
