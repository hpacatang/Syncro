<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionReviewController extends Controller
{
    public function show(Request $request, Submission $submission): View
    {
        $user = $request->user();

        if ($user->canSubmitPosts() && (int) $submission->user_id !== (int) $user->id) {
            abort(403);
        }

        if (! $user->canSubmitPosts() && ! $user->isStaffReviewer()) {
            abort(403);
        }

        $submission->load(['user', 'enhancer', 'feedback.user']);

        return view('Submission.ReviewFeatures', [
            'submission' => $submission,
        ]);
    }
}
