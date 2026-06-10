<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Notifications\SubmissionUnderReview;
use App\Services\SubmissionLifecycleService;
use App\Submission\Enums\SubmissionLifecycleStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SubmissionReviewController extends Controller
{
    public function show(Request $request, Submission $submission, SubmissionLifecycleService $lifecycle): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->canSubmitPosts() && (int) $submission->user_id !== (int) $user->id) {
            return redirect()
                ->route('org.submissions')
                ->with('error', 'You can only review your own submissions.');
        }

        if (! $user->canSubmitPosts() && ! $user->isStaffReviewer()) {
            return redirect()->route($user->homeRoute());
        }

        
        
        if ($user->isStaffReviewer() && $submission->workflow_status === SubmissionLifecycleStatus::Submitted->value) {
            try {
                $submission = $lifecycle->systemTransition($submission, SubmissionLifecycleStatus::UnderPeerReview);

                
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
