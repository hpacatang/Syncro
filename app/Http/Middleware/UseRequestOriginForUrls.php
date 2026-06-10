<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

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
