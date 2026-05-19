<?php

namespace App\Enums;

enum SubmissionLifecycleStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case UnderPeerReview = 'under_peer_review';
    case AwaitingOrgApproval = 'awaiting_org_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revised = 'revised';
    case Posted = 'posted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Submitted => 'Submitted',
            self::UnderPeerReview => 'Under Peer Review',
            self::AwaitingOrgApproval => 'Awaiting Org Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Revised => 'Revised',
            self::Posted => 'Posted',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Submitted => 'info',
            self::UnderPeerReview => 'warning',
            self::AwaitingOrgApproval => 'primary',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Revised => 'warning',
            self::Posted => 'dark',
        };
    }

    /**
     * Ordered steps for progress UI (excludes terminal rejected from main path).
     *
     * @return list<self>
     */
    public static function progressSteps(): array
    {
        return [
            self::Pending,
            self::Submitted,
            self::UnderPeerReview,
            self::AwaitingOrgApproval,
            self::Approved,
            self::Posted,
        ];
    }

    public function progressIndex(): int
    {
        $steps = self::progressSteps();
        $idx = array_search($this, $steps, true);

        if ($idx !== false) {
            return (int) $idx;
        }

        return match ($this) {
            self::Revised => 2,
            self::Rejected => -1,
            default => 0,
        };
    }

    /**
     * Map legacy workflow_status values from the database.
     */
    public static function fromLegacy(?string $value): self
    {
        return match ($value) {
            'pending_submission' => self::Submitted,
            'pending_pair_review' => self::UnderPeerReview,
            'pending_org_approval' => self::AwaitingOrgApproval,
            'approved' => self::Approved,
            'rejected' => self::Rejected,
            'posted' => self::Posted,
            'pending' => self::Pending,
            'submitted' => self::Submitted,
            'under_peer_review' => self::UnderPeerReview,
            'awaiting_org_approval' => self::AwaitingOrgApproval,
            'revised' => self::Revised,
            default => self::Pending,
        };
    }

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value) ?? self::fromLegacy($value);
    }
}
