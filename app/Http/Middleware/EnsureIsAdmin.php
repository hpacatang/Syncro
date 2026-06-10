<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

            return redirect()->route($user->homeRoute());
        }

        return $next($request);
    }
}
