<?php

namespace App\Support;

/**
 * Maps legacy and unified lifecycle workflow_status values (post-migration).
 */
class SubmissionWorkflowGroups
{
    public const SUBMITTED = ['pending_submission', 'submitted', 'pending'];

    public const IN_PEER_REVIEW = ['pending_pair_review', 'under_peer_review'];

    public const REVISED = ['revised'];

    public const AWAITING_ORG = ['pending_org_approval', 'awaiting_org_approval'];

    public const APPROVED = ['approved'];

    public const REJECTED = ['rejected'];

    public const POSTED = ['posted'];

    public const PENDING_QUEUE = ['pending_submission', 'pending_pair_review', 'submitted', 'under_peer_review', 'revised', 'pending'];

    public static function matches(string $workflowStatus, array $group): bool
    {
        return in_array($workflowStatus, $group, true);
    }
}
