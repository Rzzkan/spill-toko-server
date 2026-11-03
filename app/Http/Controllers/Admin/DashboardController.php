<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Subscription;
use App\Services\Ai;
use App\Services\Midtrans;
use App\Services\Settings;

class DashboardController extends Controller
{
    /** Hitung per-hari (WIB) untuk $days hari terakhir dari koleksi nilai tanggal. @return array{0:array,1:array} labels, buckets */
    public static function dayBuckets(int $days): array
    {
        $tz = config('temanseller.display_timezone');
        $labels = [];
        $keys = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now($tz)->subDays($i);
            $keys[] = $d->format('Y-m-d');
            $labels[] = $d->format('d/m');
        }
        return [$labels, array_fill_keys($keys, 0)];
    }

    public function index()
    {
        $tz = config('temanseller.display_timezone');
        $days = 30;
        $since = now($tz)->subDays($days - 1)->startOfDay()->utc();

        [$labels, $signups] = self::dayBuckets($days);
        foreach (self::perDay(Customer::query()->where('created_at', '>=', $since), 'created_at', 'COUNT(*)', $tz) as $k => $n) {
            if (isset($signups[$k])) $signups[$k] = (int) $n;
        }
        [, $revenue] = self::dayBuckets($days);
        foreach (self::perDay(Order::query()->where('status', 'paid')->where('paid_at', '>=', $since), 'paid_at', 'SUM(amount)', $tz) as $k => $n) {
            if (isset($revenue[$k])) $revenue[$k] = (int) $n;
        }

        $monthStart = now($tz)->startOfMonth()->utc();
        $todayStart = now($tz)->startOfDay()->utc();
        $paid = Order::query()->where('status', 'paid');
        $stats = [
            'customers' => Customer::count(),
            ...Subscription::statusCounts(),
            'revenue_month' => (int) (clone $paid)->where('paid_at', '>=', $monthStart)->sum('amount'),
            'revenue_total' => (int) (clone $paid)->sum('amount'),
            'pending' => Order::query()->where('status', 'pending')->count(),
            'ai_today' => AiLog::query()->where('created_at', '>=', $todayStart)->where('cache_hit', false)->where('status', 'ok')->count(),
            'ai_cache_today' => AiLog::query()->where('created_at', '>=', $todayStart)->where('cache_hit', true)->count(),
            'ai_err_today' => AiLog::query()->where('created_at', '>=', $todayStart)->where('status', 'error')->count(),
        ];

        // peringatan konfigurasi yang belum lengkap
        $warnings = [];
        if (!Settings::has('google.client_id')) $warnings[] = ['Login Google belum dikonfigurasi — pengguna belum bisa masuk.', 'google'];
        if (!Midtrans::configured()) $warnings[] = ['Midtrans belum dikonfigurasi — pengguna belum bisa membayar.', 'midtrans'];
        if (Settings::bool('ai.enabled') && Ai::key() === '') $warnings[] = ['API key kenari.id belum diisi — analisis AI tidak aktif.', 'ai'];

        return view('admin.dashboard', [
            'stats' => $stats, 'warnings' => $warnings,
            'signupChart' => ['type' => 'area', 'categories' => $labels, 'series' => [['name' => 'Pengguna baru', 'data' => array_values($signups)]]],
            'revenueChart' => ['type' => 'bar', 'currency' => true, 'categories' => $labels, 'series' => [['name' => 'Pendapatan', 'data' => array_values($revenue)]], 'colors' => ['#12b76a']],
            'latestOrders' => Order::query()->with('customer')->latest('id')->limit(6)->get(),
            'latestCustomers' => Customer::query()->with('subscriptions')->latest('id')->limit(6)->get(),
        ]);
    }

    /**
     * Agregat per hari (zona waktu tampilan) langsung di database: satu query, bukan menarik semua baris ke PHP.
     * Zona tampilan dipakai sebagai selisih menit tetap (Asia/Jakarta tanpa DST).
     * @return array<string,int|string> 'Y-m-d' => nilai agregat
     */
    private static function perDay($query, string $col, string $agg, string $tz): array
    {
        $min = (int) now($tz)->getOffset() / 60;
        $day = $query->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', $col, '" . ($min >= 0 ? '+' : '-') . abs($min) . " minutes')"
            : "DATE_FORMAT(DATE_ADD($col, INTERVAL $min MINUTE), '%Y-%m-%d')";
        return $query->selectRaw("$day AS d, $agg AS n")->groupBy('d')->pluck('n', 'd')->all();
    }
}
