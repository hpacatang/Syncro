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
        $submission = Submission::factory()->submitted()->create();

        $service = app(SubmissionLifecycleService::class);
        $updated = $service->systemTransition(
            $submission,
            SubmissionLifecycleStatus::UnderPeerReview
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

    public function test_rejected_status_has_no_further_transitions_and_cannot_be_undone(): void
    {
        $admin = User::factory()->admin()->create();
        $submission = Submission::factory()->rejected()->create();

        $service = app(SubmissionLifecycleService::class);
        $allowed = $service->allowedTransitions($submission, $admin);

        $this->assertEmpty($allowed);

        $this->expectException(InvalidLifecycleTransitionException::class);
        $service->transition($submission, SubmissionLifecycleStatus::UnderPeerReview, $admin);
    }

    public function test_all_account_roles_can_reject_submissions(): void
    {
        $service = app(SubmissionLifecycleService::class);

        $admin = User::factory()->admin()->create();
        $pair = User::factory()->pair()->create();
        $dept = User::factory()->department()->create();
        $org = User::factory()->org()->create();

        $subAdmin = Submission::factory()->awaitingOrgApproval()->create();
        $subPair = Submission::factory()->awaitingOrgApproval()->create();
        $subDept = Submission::factory()->submitted()->create(['review_department_id' => $dept->department_id]);
        $subOrg = Submission::factory()->awaitingOrgApproval()->create(['user_id' => $org->id]);

        $this->assertTrue($service->canTransition($subAdmin, $admin, SubmissionLifecycleStatus::Rejected));
        $this->assertTrue($service->canTransition($subPair, $pair, SubmissionLifecycleStatus::Rejected));
        $this->assertTrue($service->canTransition($subDept, $dept, SubmissionLifecycleStatus::Rejected));
        $this->assertTrue($service->canTransition($subOrg, $org, SubmissionLifecycleStatus::Rejected));
    }
}
