<?php

namespace Tests\Unit;

use App\Submission\Enums\SubmissionLifecycleStatus;
use App\Submission\Exceptions\InvalidLifecycleTransitionException;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_move_submitted_to_under_peer_review(): void
    {
        $staff = User::factory()->create(['role' => 'pair']);
        $submission = Submission::factory()->submitted()->create();

        $service = app(SubmissionLifecycleService::class);
        $updated = $service->transition(
            $submission,
            SubmissionLifecycleStatus::UnderPeerReview,
            $staff
        );

        $this->assertSame(SubmissionLifecycleStatus::UnderPeerReview->value, $updated->workflow_status);
    }

    public function test_org_cannot_approve_unless_under_peer_review(): void
    {
        $org = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $org->id]);

        $service = app(SubmissionLifecycleService::class);

        $this->expectException(InvalidLifecycleTransitionException::class);
        $service->transition($submission, SubmissionLifecycleStatus::Approved, $org);
    }

    public function test_org_can_approve_when_under_peer_review(): void
    {
        $org = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->awaitingOrgApproval()->create(['user_id' => $org->id]);

        $service = app(SubmissionLifecycleService::class);
        $updated = $service->transition($submission, SubmissionLifecycleStatus::Approved, $org);

        $this->assertSame(SubmissionLifecycleStatus::Approved->value, $updated->workflow_status);
    }
}
