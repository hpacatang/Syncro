<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('login');
        }

        $userRole = auth()->user()->role;

        // Normalize the user's role and the allowed roles for robust comparison
        $userRoleNormalized = strtolower(trim((string) $userRole));

        // Middleware parameters can sometimes come as a single comma-separated string
        $allowed = [];
        foreach ($roles as $r) {
            foreach (explode(',', (string) $r) as $part) {
                $allowed[] = strtolower(trim($part));
            }
        }

        if (!in_array($userRoleNormalized, $allowed, true)) {
            // Log for debugging so we can inspect mismatches in runtime
            \Log::warning('CheckRole: access denied', [
                'user_id' => auth()->id(),
                'user_role' => $userRole,
                'required_roles' => $allowed,
            ]);

            return response()->view('errors.unauthorized', [], 403);
        }

        return $next($request);
    }
}
