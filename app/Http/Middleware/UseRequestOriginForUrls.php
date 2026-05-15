<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aligns generated absolute URLs with the current request host/scheme when they
 * disagree with APP_URL (e.g. localhost in .env but browsing via 127.0.0.1).
 *
 * Otherwise route() targets another origin, the session cookie is not sent on POST,
 * and CSRF validation fails with HTTP 419.
 */
final class UseRequestOriginForUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = $request->getHost();

        if (
            is_string($configuredHost) && $configuredHost !== ''
            && $requestHost !== ''
            && strcasecmp($configuredHost, $requestHost) !== 0
        ) {
            URL::forceRootUrl($request->getSchemeAndHttpHost());
        }

        return $next($request);
    }
}
