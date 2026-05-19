<?php

namespace App\Services;

use App\Enums\SubmissionLifecycleStatus;
use App\Exceptions\InvalidLifecycleTransitionException;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SubmissionLifecycleService
{
    /**
     * Allowed transitions: from => [to, ...]
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'pending' => ['submitted'],
        'submitted' => ['under_peer_review', 'rejected'],
        'under_peer_review' => ['awaiting_org_approval', 'rejected', 'revised'],
        'awaiting_org_approval' => ['approved', 'revised', 'rejected'],
        'revised' => ['under_peer_review', 'awaiting_org_approval', 'rejected'],
        'approved' => ['posted', 'revised'],
        'rejected' => ['under_peer_review', 'revised'],
        'posted' => [],
    ];

    /**
     * Which roles may trigger each target status manually.
     *
     * @var array<string, list<string>>
     */
    private const ROLE_TARGETS = [
        'admin' => ['under_peer_review', 'awaiting_org_approval', 'approved', 'rejected', 'revised', 'posted'],
        'pair' => ['under_peer_review', 'awaiting_org_approval', 'approved', 'rejected', 'revised', 'posted'],
        'org' => ['approved', 'revised'],
    ];

    public function current(Submission $submission): SubmissionLifecycleStatus
    {
        return SubmissionLifecycleStatus::fromLegacy($submission->workflow_status);
    }

    /**
     * @return list<SubmissionLifecycleStatus>
     */
    public function allowedTransitions(Submission $submission, User $actor): array
    {
        $current = $this->current($submission);
        $targets = self::TRANSITIONS[$current->value] ?? [];

        return array_values(array_filter(
            array_map(
                fn (string $value) => SubmissionLifecycleStatus::from($value),
                $targets
            ),
            fn (SubmissionLifecycleStatus $target) => $this->canTransition($submission, $actor, $target)
        ));
    }

    public function canTransition(
        Submission $submission,
        User $actor,
        SubmissionLifecycleStatus $target,
        ?SubmissionLifecycleStatus $from = null
    ): bool {
        $from ??= $this->current($submission);

        if ($from === $target) {
            return false;
        }

        $allowed = self::TRANSITIONS[$from->value] ?? [];
        if (! in_array($target->value, $allowed, true)) {
            return false;
        }

        return $this->actorMaySet($submission, $actor, $target);
    }

    public function transition(
        Submission $submission,
        SubmissionLifecycleStatus $target,
        User $actor,
        array $context = []
    ): Submission {
        $from = $this->current($submission);

        if (! $this->canTransition($submission, $actor, $target, $from)) {
            throw new InvalidLifecycleTransitionException(
                sprintf(
                    'Cannot move submission #%d from %s to %s.',
                    $submission->id,
                    $from->label(),
                    $target->label()
                )
            );
        }

        $submission->workflow_status = $target->value;
        $submission->status = $this->legacyStatusFor($target);

        if (isset($context['org_review_notes'])) {
            $submission->org_review_notes = $context['org_review_notes'];
        }

        if (isset($context['pair_feedback'])) {
            $submission->pair_feedback = $context['pair_feedback'];
        }

        $submission->save();

        Log::info('Submission lifecycle transition', [
            'submission_id' => $submission->id,
            'from' => $from->value,
            'to' => $target->value,
            'actor_id' => $actor->id,
            'actor_role' => $actor->role,
        ]);

        return $submission->fresh();
    }

    /**
     * System transitions used by automated flows (enhance, submit, etc.).
     */
    public function systemTransition(Submission $submission, SubmissionLifecycleStatus $target, array $context = []): Submission
    {
        $from = $this->current($submission);
        $allowed = self::TRANSITIONS[$from->value] ?? [];

        if (! in_array($target->value, $allowed, true) && $from !== $target) {
            throw new InvalidLifecycleTransitionException(
                sprintf('System cannot move submission #%d from %s to %s.', $submission->id, $from->value, $target->value)
            );
        }

        $submission->workflow_status = $target->value;
        $submission->status = $this->legacyStatusFor($target);

        if (isset($context['org_review_notes'])) {
            $submission->org_review_notes = $context['org_review_notes'];
        }

        if (isset($context['pair_feedback'])) {
            $submission->pair_feedback = $context['pair_feedback'];
        }

        $submission->save();

        return $submission->fresh();
    }

    private function actorMaySet(Submission $submission, User $actor, SubmissionLifecycleStatus $target): bool
    {
        if ($actor->isAdmin() || $actor->isPair()) {
            $staffTargets = self::ROLE_TARGETS['admin'];

            return in_array($target->value, $staffTargets, true);
        }

        if ($actor->isOrg()) {
            if ((int) $submission->user_id !== (int) $actor->id) {
                return false;
            }

            return in_array($target->value, self::ROLE_TARGETS['org'], true)
                && $this->current($submission) === SubmissionLifecycleStatus::AwaitingOrgApproval;
        }

        return false;
    }

    private function legacyStatusFor(SubmissionLifecycleStatus $status): string
    {
        return match ($status) {
            SubmissionLifecycleStatus::Approved,
            SubmissionLifecycleStatus::Posted => 'approved',
            SubmissionLifecycleStatus::UnderPeerReview,
            SubmissionLifecycleStatus::AwaitingOrgApproval,
            SubmissionLifecycleStatus::Revised => 'under_review',
            default => 'pending',
        };
    }
}
