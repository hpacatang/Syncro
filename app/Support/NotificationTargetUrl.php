<?php

namespace App\Support;

use App\Models\Submission;
use App\Models\User;

class NotificationTargetUrl
{
    /**
     * Resolve the best click target for a database notification payload.
     */
    public static function resolve(array $data, User $user): string
    {
        if (! empty($data['review_url'])) {
            return $data['review_url'];
        }

        $submissionId = $data['submission_id'] ?? null;
        if ($submissionId) {
            $submission = Submission::find($submissionId);
            if ($submission) {
                if ($user->canSubmitPosts()) {
                    return route('org.submissions.review', $submission);
                }

                $needsEnhance = ! empty($data['open_enhance'])
                    || ($data['type'] ?? '') === 'submission_queued'
                    || in_array($submission->workflow_status, [
                        'pending_submission', 'pending_pair_review',
                        'submitted', 'under_peer_review', 'revised',
                    ], true);

                if ($needsEnhance) {
                    return route('dashboard', ['enhance' => $submission->id]);
                }

                return route('dashboard.submissions.review', $submission);
            }
        }

        if (! empty($data['url'])) {
            return $data['url'];
        }

        return $user->canSubmitPosts()
            ? route('org.notifications')
            : route('dashboard.notifications');
    }
}
