<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('login');
        }

        $user = auth()->user();
        $userRole = $user->role ?? 'unknown';

        $userRoleNormalized = strtolower(trim((string) $userRole));

        $allowed = [];
        foreach ($roles as $r) {
            foreach (explode(',', (string) $r) as $part) {
                $allowed[] = strtolower(trim($part));
            }
        }

        if (!in_array($userRoleNormalized, $allowed, true)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to access this resource',
                ], 403);
            }

            return response()->view('errors.unauthorized', [
                'userRole' => $userRole,
                'requiredRoles' => $allowed,
            ], 403);
        }

        return $next($request);
    }
}
