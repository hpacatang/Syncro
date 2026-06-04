<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Notifications\SubmissionUnderReview;
use App\Services\SubmissionLifecycleService;
use App\Submission\Enums\SubmissionLifecycleStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SubmissionReviewController extends Controller
{
    public function show(Request $request, Submission $submission, SubmissionLifecycleService $lifecycle): View
    {
        $user = $request->user();

        if ($user->canSubmitPosts() && (int) $submission->user_id !== (int) $user->id) {
            abort(403);
        }

        if (! $user->canSubmitPosts() && ! $user->isStaffReviewer()) {
            abort(403);
        }

        // Auto-transition to under_peer_review when PAIR/Admin opens a submitted submission.
        // Only fires once — subsequent opens find it already under_peer_review and skip this block.
        if ($user->isStaffReviewer() && $submission->workflow_status === SubmissionLifecycleStatus::Submitted->value) {
            try {
                $submission = $lifecycle->systemTransition($submission, SubmissionLifecycleStatus::UnderPeerReview);

                // Notify the org user so they know their submission is now being reviewed.
                $orgUser = $submission->user;
                if ($orgUser) {
                    $orgUser->notify(new SubmissionUnderReview($submission));
                }
            } catch (\Exception $e) {
                Log::error('Failed auto-transitioning submission #' . $submission->id . ' to under_peer_review: ' . $e->getMessage());
            }
        }

        $submission->load(['user', 'enhancer', 'feedback.user']);

        return view('Submission.ReviewFeatures', [
            'submission' => $submission,
        ]);
    }
}
