<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $q = Order::query()->with('customer');
        if (in_array($st = (string) $r->query('status'), ['pending', 'paid', 'expired', 'cancelled', 'denied', 'failed'], true)) $q->where('status', $st);
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('order_id', 'like', "%$s%")->orWhereHas('customer', fn ($c) => $c->where('email', 'like', "%$s%")));
        }
        return view('admin.orders', [
            'orders' => $q->latest('id')->paginate(25)->withQueryString(),
            'total' => (int) Order::query()->where('status', 'paid')->sum('amount'),
        ]);
    }
}
