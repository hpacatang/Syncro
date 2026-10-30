<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePairFacebookConnected
{
    /**
     * Enforce strict Facebook OAuth Cloud Sync exclusively for the Pair role.
     * The Pair user must have a persistent active connection to operate.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Strict requirement applies exclusively to the Pair role
        if ($user->role === 'pair') {
            $isConnected = ! empty($user->fb_connected_at) && ! empty($user->fb_access_token);

            if (! $isConnected) {
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success'          => false,
                        'error'            => 'FacebookOAuthRequired',
                        'message'          => 'Strict Pair requirement: You must be persistently connected to Facebook OAuth (Cloud Sync) to perform this operation.',
                        'authorization_url'=> route('pair.facebook.connect'),
                    ], 428); // 428 Precondition Required
                }

                return redirect()
                    ->route('pair.facebook.connect')
                    ->with('warning', 'Pair accounts require persistent Facebook Cloud Sync authorization before operating.');
            }
        }

        return $next($request);
    }
}
