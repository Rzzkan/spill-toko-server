<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;

/** Sesi web pengguna (login Google) -> $request->attributes['customer'] */
class PortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        $c = ($id = $request->session()->get('customer_id')) ? Customer::query()->find($id) : null;
        if (!$c) {
            $request->session()->forget('customer_id');
            return redirect()->route('portal.login');
        }
        $request->attributes->set('customer', $c);
        return $next($request);
    }
}
