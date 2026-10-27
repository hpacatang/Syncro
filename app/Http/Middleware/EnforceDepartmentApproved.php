<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDepartmentApproved
{
    /**
     * Block department-role users from posting routes unless their account
     * has been approved by an administrator.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isDepartment() && $user->account_status !== 'approved') {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your department account is pending approval. Contact your administrator.',
                ], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Your department account is pending approval. Please contact your administrator.');
        }

        return $next($request);
    }
}
