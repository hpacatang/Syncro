<?php

namespace Tests\Unit;

use App\Submission\Enums\SubmissionLifecycleStatus;
use PHPUnit\Framework\TestCase;

class SubmissionLifecycleStatusTest extends TestCase
{
    public function test_progress_index_mapping_is_correct(): void
    {
        $this->assertSame(0, SubmissionLifecycleStatus::Submitted->progressIndex());
        $this->assertSame(1, SubmissionLifecycleStatus::UnderPeerReview->progressIndex());
        $this->assertSame(1, SubmissionLifecycleStatus::Revised->progressIndex());
        $this->assertSame(2, SubmissionLifecycleStatus::Approved->progressIndex());
        $this->assertSame(3, SubmissionLifecycleStatus::Posted->progressIndex());
        $this->assertSame(-1, SubmissionLifecycleStatus::Rejected->progressIndex());
    }

    public function test_progress_colors_are_correct(): void
    {
        $this->assertSame('#22c55e', SubmissionLifecycleStatus::Submitted->progressColor());
        $this->assertSame('#f97316', SubmissionLifecycleStatus::UnderPeerReview->progressColor());
        $this->assertSame('#2563eb', SubmissionLifecycleStatus::Approved->progressColor());
        $this->assertSame('#ef4444', SubmissionLifecycleStatus::Rejected->progressColor());
        $this->assertSame('#f97316', SubmissionLifecycleStatus::Revised->progressColor());
        $this->assertSame('#7c3aed', SubmissionLifecycleStatus::Posted->progressColor());
    }
}
