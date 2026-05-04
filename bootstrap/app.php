<?php

use App\Http\Middleware\ForceLocalProxyRootUrl;
use App\Support\RegisterSecurityLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(ForceLocalProxyRootUrl::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $e instanceof HttpExceptionInterface || $e->getStatusCode() !== 429) {
                return null;
            }
            if (! $request->is('register') || ! $request->isMethod('POST')) {
                return null;
            }
            Log::warning(RegisterSecurityLog::LOG_KEY, [
                'event' => 'register_throttled',
                'ip' => $request->ip(),
            ]);

            return null;
        });
    })->create();
