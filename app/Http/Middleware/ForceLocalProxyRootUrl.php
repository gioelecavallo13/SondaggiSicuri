<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Con Traefik (o altro reverse proxy) in locale, Host può essere sondaggi.local o sondaggi.localhost
 * mentre APP_URL nel .env ne fissa uno solo: url(), route() e redirect userebbero l’host sbagliato.
 * Allinea la root URL alla richiesta corrente. Esclude 127.0.0.1 (healthcheck Docker su artisan :10000).
 */
class ForceLocalProxyRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') === 'local' && $request->getHost() !== '127.0.0.1') {
            URL::forceRootUrl($request->getScheme().'://'.$request->getHttpHost());
        }

        return $next($request);
    }
}
