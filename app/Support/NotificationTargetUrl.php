<?php

namespace App\Support;

use App\Models\Submission;
use App\Models\User;

class NotificationTargetUrl
{
    public static function resolve(array $data, User $user): string
    {
        if (! empty($data['review_url'])) {
            return $data['review_url'];
        }

        $submissionId = $data['submission_id'] ?? null;

        if (! $submissionId && ! empty($data['url'])) {
            if (preg_match('/submissions\/(\d+)/', $data['url'], $matches)
                || preg_match('/[?&](?:enhance|submission)=(\d+)/', $data['url'], $matches)) {
                $submissionId = (int) $matches[1];
            }
        }

        if ($submissionId) {
            $submission = Submission::withoutGlobalScopes()->find($submissionId);

            if ($user->isOrg()) {
                if ($submission) {
                    return route('org.submissions.review', $submission);
                }
                return route('org.submissions');
            }

            // For staff reviewers (PAIR, Department, Super Admin, Admin):
            // Direct straight to the submission review page: /dashboard/submissions/{id}/review
            if ($submission) {
                return route('dashboard.submissions.review', $submission);
            }

            return route('dashboard.submissions.review', (int) $submissionId);
        }

        if (! empty($data['url'])) {
            return $data['url'];
        }

        return $user->isOrg()
            ? route('org.notifications')
            : route('dashboard.notifications');
    }
}

