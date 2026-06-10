<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiRole
{
    
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth('sanctum')->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $userRole = auth('sanctum')->user()->role;

        
        $userRoleNormalized = strtolower(trim((string) $userRole));

        $allowed = [];
        foreach ($roles as $r) {
            foreach (explode(',', (string) $r) as $part) {
                $allowed[] = strtolower(trim($part));
            }
        }

        if (!in_array($userRoleNormalized, $allowed, true)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You do not have permission to access this resource'
            ], 403);
        }

        return $next($request);
    }
}
