<?php

use App\Exceptions\ApiException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
        $middleware->validateCsrfTokens(except: ['api/*']);
        $middleware->api(prepend: [\Illuminate\Http\Middleware\HandleCors::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport(ApiException::class);
        $exceptions->shouldRenderJsonWhen(fn (Request $r, Throwable $e) => $r->is('api/*'));
        // API: pesan galat singkat {"error": "..."} sesuai kontrak extension
        $exceptions->render(function (Throwable $e, Request $r) {
            if (!$r->is('api/*') || $e instanceof ApiException) return null;
            $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500;
            if ($status >= 500) report($e);
            return response()->json(['error' => $status === 404 ? 'Endpoint tidak ditemukan' : ($status >= 500 ? 'Terjadi kesalahan server' : 'Permintaan tidak valid')], $status);
        });
    })->create();
