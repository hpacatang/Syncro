<?php

namespace App\Http\Middleware;

use App\Support\TextPayloadCompressor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressTextPayload
{
    /**
     * Handle an incoming request and compress string payloads before reaching controllers/models.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $compressed = TextPayloadCompressor::compressArray($request->all());
        $request->merge($compressed);

        return $next($request);
    }
}
