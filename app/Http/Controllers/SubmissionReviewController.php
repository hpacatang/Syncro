<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\AppSetting;
use App\Notifications\SubmissionUnderReview;
use App\Services\SubmissionLifecycleService;
use App\Submission\Enums\SubmissionLifecycleStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubmissionReviewController extends Controller
{
    public function show(Request $request, Submission $submission, SubmissionLifecycleService $lifecycle): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isOrg() && (int) $submission->user_id !== (int) $user->id) {
            return redirect()
                ->route('org.submissions')
                ->with('error', 'You can only review your own submissions.');
        }

        if (! $user->canSubmitPosts() && ! $user->isStaffReviewer()) {
            return redirect()->route($user->homeRoute());
        }

        $submission = $this->beginStaffReview($submission, $user->isStaffReviewer(), $lifecycle);

        $submission->load(['user', 'enhancer', 'feedback.user']);

        return view('Submission.ReviewFeatures', [
            'submission' => $submission,
        ]);
    }

    public function evaluate(Request $request, Submission $submission, SubmissionLifecycleService $lifecycle): View|RedirectResponse
    {
        abort_unless(
            $request->user()->isStaffReviewer() && Gate::allows('reviewAsStaff', $submission),
            403
        );

        // A rejection is final: show the content read-only instead of the editing workspace.
        if ($submission->workflow_status === SubmissionLifecycleStatus::Rejected->value) {
            return redirect()
                ->route('dashboard.submissions.review', $submission)
                ->with('error', 'This submission was rejected and can only be viewed.');
        }

        $submission = $this->beginStaffReview($submission, true, $lifecycle);
        $submission->load(['user', 'reviewDepartment', 'media', 'enhancer']);

        return view('Submission.Evaluate', [
            'submission' => $submission,
            'configuredProviders' => [
                'openai' => (bool) config('services.openai.key'),
                'gemini' => (bool) config('services.gemini.key'),
                'deepseek' => (bool) config('services.deepseek.key'),
            ],
            'defaultTone' => AppSetting::get('caption_tone', 'formal'),
        ]);
    }

    private function beginStaffReview(
        Submission $submission,
        bool $isStaffReviewer,
        SubmissionLifecycleService $lifecycle
    ): Submission {
        if (! $isStaffReviewer
            || ! Gate::allows('reviewAsStaff', $submission)
            || $submission->workflow_status !== SubmissionLifecycleStatus::Submitted->value) {
            return $submission;
        }

        try {
            $submission = $lifecycle->systemTransition($submission, SubmissionLifecycleStatus::UnderPeerReview);
            $submission->user?->notify(new SubmissionUnderReview($submission));
        } catch (\Exception $e) {
            Log::error('Failed auto-transitioning submission to under_peer_review.', [
                'submission_id' => $submission->id,
                'exception' => $e::class,
            ]);
        }

        return $submission;
    }
}
