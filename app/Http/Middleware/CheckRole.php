<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();
        $userRole = $user->role ?? 'unknown';

        $userRoleNormalized = strtolower(trim((string) $userRole));

        $allowed = [];
        foreach ($roles as $r) {
            foreach (explode(',', (string) $r) as $part) {
                $allowed[] = strtolower(trim($part));
            }
        }

        if (! in_array($userRoleNormalized, $allowed, true)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to access this resource',
                ], 403);
            }

            return redirect()->route($user->homeRoute());
            // return redirect()->route('home')->with('error', 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
