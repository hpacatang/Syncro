<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if (! $user->is_admin && ! $user->is_pair) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            return redirect()->route($user->homeRoute());
            // return redirect()->route('home')->with('error', 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
