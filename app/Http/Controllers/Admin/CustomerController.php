<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\App as AppModel;
use App\Models\Customer;
use App\Models\Subscription;
use App\Services\AppCatalog;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $r)
    {
        $app = $this->appSlug($r);
        $q = Customer::query()->withCount('orders')->with('subscriptions');
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('email', 'like', "%$s%")->orWhere('name', 'like', "%$s%"));
        }
        if (in_array($st = (string) $r->query('status'), ['trial', 'monthly', 'lifetime', 'expired'], true)) $q->status($st, $app);
        return view('admin.customers.index', [
            'customers' => $q->latest('id')->paginate(20)->withQueryString(),
            'app' => $app, 'apps' => AppModel::listAll(),
            'counts' => ['' => Customer::count()] + Subscription::statusCounts($app),
        ]);
    }

    private function appSlug(Request $r): string
    {
        $a = (string) ($r->query('app') ?: $r->input('app'));
        return $a !== '' && AppCatalog::find($a) ? $a : AppModel::DEFAULT;
    }

    public function show(Customer $customer)
    {
        $customer->load('subscriptions');
        return view('admin.customers.show', [
            'c' => $customer,
            'apps' => AppModel::listAll(),
            'orders' => $customer->orders()->latest('id')->limit(30)->get(),
            'logs' => AdminLog::query()->where('customer_id', $customer->id)->with('admin')->latest('id')->limit(20)->get(),
            'aiLogs' => $customer->aiLogs()->latest('created_at')->limit(15)->get(),
        ]);
    }

    public function update(Request $r, Customer $customer)
    {
        $act = (string) $r->input('action');
        $days = max(1, min(3650, (int) $r->input('days', 30)));
        $slug = $this->appSlug($r);
        $name = AppCatalog::find($slug)?->name ?: $slug;
        $sub = $customer->subscription($slug, true);
        switch ($act) {
            case 'add_days':       // perpanjang langganan bulanan
                $sub->extend($days, 'monthly');
                $note = "[$name] Tambah $days hari langganan";
                break;
            case 'add_trial':      // atur trial dari sekarang
                $sub->update(['trial_ends_at' => now()->addDays($days)]);
                $note = "[$name] Trial diatur $days hari dari sekarang";
                break;
            case 'lifetime':
                $sub->update(['lifetime' => true]);
                $note = "[$name] Diberi Lifetime";
                break;
            case 'unlifetime':
                $sub->update(['lifetime' => false]);
                $note = "[$name] Lifetime dicabut";
                break;
            case 'revoke':
                $sub->update(['lifetime' => false, 'paid_until' => null, 'trial_ends_at' => now()]);
                $note = "[$name] Akses dicabut";
                break;
            default:
                return back()->with('error', 'Aksi tidak dikenal.');
        }
        AdminLog::record($act, $note, $customer->id);
        return back()->with('ok', $note . '.');
    }
}
