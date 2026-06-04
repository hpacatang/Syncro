<?php

namespace App\Submission\Enums;

enum SubmissionLifecycleStatus: string
{
    case Submitted = 'submitted';
    case UnderPeerReview = 'under_peer_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revised = 'revised';
    case Posted = 'posted';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderPeerReview => 'Under PAIR Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Revised => 'Revised',
            self::Posted => 'Posted',
        };
    }

    /** Action label for PAIR step buttons (one click per finished step). */
    public function actionLabel(): string
    {
        return match ($this) {
            self::UnderPeerReview => 'Start PAIR review',
            self::Approved => 'Approve post',
            self::Rejected => 'Reject post',
            self::Revised => 'Send back for revision',
            self::Posted => 'Mark as posted',
            default => $this->label(),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Submitted => 'info',
            self::UnderPeerReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Revised => 'warning',
            self::Posted => 'dark',
        };
    }

    public function progressTheme(): string
    {
        return $this->value;
    }

    public function progressColor(): string
    {
        return match ($this) {
            self::Submitted => '#22c55e',
            self::UnderPeerReview => '#f97316',
            self::Approved => '#2563eb',
            self::Rejected => '#ef4444',
            self::Revised => '#eab308',
            self::Posted => '#7c3aed',
        };
    }

    /** RGB components for CSS glow animations (e.g. `249, 115, 22`). */
    public function progressGlowRgb(): string
    {
        return match ($this) {
            self::Submitted => '34, 197, 94',
            self::UnderPeerReview => '249, 115, 22',
            self::Approved => '37, 99, 235',
            self::Rejected => '239, 68, 68',
            self::Revised => '234, 179, 8',
            self::Posted => '124, 58, 237',
        };
    }

    public static function progressSteps(): array
    {
        return [
            self::Submitted,
            self::UnderPeerReview,
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

    public static function fromLegacy(?string $value): self
    {
        return match ($value) {
            'pending_submission' => self::Submitted,
            'pending_pair_review' => self::UnderPeerReview,
            'pending_org_approval' => self::UnderPeerReview,
            'approved' => self::Approved,
            'rejected' => self::Rejected,
            'posted' => self::Posted,
            'pending' => self::Submitted,
            'submitted' => self::Submitted,
            'under_peer_review' => self::UnderPeerReview,
            'awaiting_org_approval' => self::UnderPeerReview,
            'revised' => self::Revised,
            default => self::Submitted,
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
