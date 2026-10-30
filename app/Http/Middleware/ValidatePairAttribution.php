<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\Submission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidatePairAttribution
{
    /**
     * Strictly verifies and logs that content operations/uploads are executed
     * specifically by an authenticated and assigned Pair user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error'   => 'Unauthenticated',
                'message' => 'Attribution failure: Authentication required.',
            ], 401);
        }

        // Allow super_admin or admin override if needed, but strictly enforce for pair-scoped actions
        $submissionId = $request->route('submission') 
            ?? $request->route('id') 
            ?? $request->input('submission_id');

        if ($submissionId) {
            $submission = $submissionId instanceof Submission 
                ? $submissionId 
                : Submission::withoutGlobalScopes()->find($submissionId);

            if ($submission) {
                // If the user claims pair role, verify strict assignment
                if ($user->role === 'pair') {
                    // When submission is assigned to a specific pair, match pair_id
                    $pairId = $user->pair_id ?? $user->department_id; // backward-compat accessor
                    $assignedReviewPairId = $submission->review_pair_id ?? $submission->review_department_id;

                    if ($assignedReviewPairId !== null && (int) $assignedReviewPairId !== (int) $pairId) {
                        Log::warning('Pair attribution mismatch detected', [
                            'user_id' => $user->id,
                            'user_pair_id' => $pairId,
                            'submission_review_pair_id' => $assignedReviewPairId,
                            'submission_id' => $submission->id,
                            'ip' => $request->ip(),
                        ]);

                        return response()->json([
                            'success' => false,
                            'error'   => 'AttributionMismatch',
                            'message' => 'Action prohibited: Content is not assigned to your Pair unit.',
                        ], 403);
                    }
                }
            }
        }

        // Log successful Pair attribution verification
        if ($user->role === 'pair') {
            Log::info('Pair attribution verified', [
                'user_id'    => $user->id,
                'pair_id'    => $user->pair_id ?? $user->department_id,
                'action_url' => $request->path(),
                'method'     => $request->method(),
                'ip'         => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
