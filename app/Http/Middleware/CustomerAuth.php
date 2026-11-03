<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\CustomerDevice;
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
        if (!\Illuminate\Support\Facades\Schema::hasTable('customer_devices')) { $request->attributes->set('customer', $customer); return $next($request); }
        // token harus terikat ke browser yang masih terdaftar (dicabut lewat halaman Akun -> sesi berakhir)
        $dev = $customer ? CustomerDevice::query()->where('customer_id', $customer->id)->find((int) ($claims['dev'] ?? 0)) : null;
        if (!$dev) throw new ApiException('Browser ini sudah dilepas dari akun atau sesi lama. Silakan masuk ulang.', 401);
        if (!$dev->last_seen_at || $dev->last_seen_at->diffInMinutes(now()) >= 10) $dev->forceFill(['last_seen_at' => now(), 'ip' => $request->ip()])->save();
        $request->attributes->set('customer', $customer);
        return $next($request);
    }
}
