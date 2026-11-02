<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Services\Jwt;
use Closure;
use Illuminate\Http\Request;

/** Bearer JWT dari extension -> $request->attributes['customer'] */
class CustomerAuth
{
    public function handle(Request $request, Closure $next)
    {
        $claims = Jwt::verify((string) $request->bearerToken());
        $customer = $claims ? Customer::query()->find((int) ($claims['sub'] ?? 0)) : null;
        if (!$customer) throw new ApiException('Sesi tidak valid, silakan login ulang', 401);
        $request->attributes->set('customer', $customer);
        return $next($request);
    }
}
