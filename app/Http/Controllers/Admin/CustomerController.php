<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $r)
    {
        $q = Customer::query()->withCount('orders');
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('email', 'like', "%$s%")->orWhere('name', 'like', "%$s%"));
        }
        if (in_array($st = (string) $r->query('status'), ['trial', 'monthly', 'lifetime', 'expired'], true)) $q->status($st);
        return view('admin.customers.index', [
            'customers' => $q->latest('id')->paginate(20)->withQueryString(),
            'counts' => ['' => Customer::count()] + collect(['trial', 'monthly', 'lifetime', 'expired'])->mapWithKeys(fn ($s) => [$s => Customer::status($s)->count()])->all(),
        ]);
    }

    public function show(Customer $customer)
    {
        return view('admin.customers.show', [
            'c' => $customer,
            'orders' => $customer->orders()->latest('id')->limit(30)->get(),
            'logs' => AdminLog::query()->where('customer_id', $customer->id)->with('admin')->latest('id')->limit(20)->get(),
            'aiLogs' => $customer->aiLogs()->latest('created_at')->limit(15)->get(),
        ]);
    }

    public function update(Request $r, Customer $customer)
    {
        $act = (string) $r->input('action');
        $days = max(1, min(3650, (int) $r->input('days', 30)));
        switch ($act) {
            case 'add_days':       // perpanjang langganan bulanan
                $base = $customer->paid_until && $customer->paid_until->isFuture() ? $customer->paid_until : now();
                $customer->update(['paid_until' => $base->copy()->addDays($days)]);
                $note = "Tambah $days hari langganan";
                break;
            case 'add_trial':      // perpanjang trial dari sekarang
                $customer->update(['trial_ends_at' => now()->addDays($days)]);
                $note = "Trial diatur $days hari dari sekarang";
                break;
            case 'lifetime':
                $customer->update(['lifetime' => true]);
                $note = 'Diberi Lifetime';
                break;
            case 'unlifetime':
                $customer->update(['lifetime' => false]);
                $note = 'Lifetime dicabut';
                break;
            case 'revoke':
                $customer->update(['lifetime' => false, 'paid_until' => null, 'trial_ends_at' => now()]);
                $note = 'Akses dicabut';
                break;
            default:
                return back()->with('error', 'Aksi tidak dikenal.');
        }
        AdminLog::query()->create(['user_id' => $r->user()->id, 'customer_id' => $customer->id, 'action' => $act, 'note' => $note]);
        return back()->with('ok', $note . '.');
    }
}
