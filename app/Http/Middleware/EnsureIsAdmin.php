<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff-only (admin or PAIR). Registered as middleware alias "isAdmin".
 */
class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isPair()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            return response()->view('errors.unauthorized', [
                'userRole' => $user->role,
                'requiredRoles' => ['admin', 'pair'],
            ], 403);
        }

        return $next($request);
    }
}
