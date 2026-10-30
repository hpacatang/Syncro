<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelFeedbackWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $name): Department
    {
        return Department::create(['department_name' => $name]);
    }

    public function test_department_can_open_resolution_letter_for_own_submission_only(): void
    {
        $dept = $this->department('School of Engineering');
        $other = $this->department('School of Business');
        $reviewer = User::factory()->department()->create(['department_id' => $dept->id]);
        $student = User::factory()->org()->create(['department_id' => $dept->id]);

        $own = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => $dept->id,
        ]);
        $foreign = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => $other->id,
        ]);

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.resolution-letter', $own))
            ->assertOk()
            ->assertSee('Public Affairs and Institutional Relations')
            ->assertSee('Meta posting billing')
            ->assertSee('Interview coordination');

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.resolution-letter', $foreign))
            ->assertNotFound();
    }

    public function test_pair_can_open_resolution_letter_and_evaluate_page_links_to_it(): void
    {
        $pair = User::factory()->pair()->create();
        $student = User::factory()->org()->create();
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => null,
        ]);

        $this->actingAs($pair)
            ->get(route('dashboard.submissions.resolution-letter', $submission))
            ->assertOk();

        $this->actingAs($pair)
            ->get(route('dashboard.submissions.evaluate', $submission))
            ->assertOk()
            ->assertSee('id="resolutionLetterLink"', false)
            ->assertSee('id="rejectButton"', false);
    }

    public function test_org_cannot_open_resolution_letter(): void
    {
        $student = User::factory()->org()->create();
        $submission = Submission::factory()->submitted()->create(['user_id' => $student->id]);

        $this->actingAs($student)
            ->get(route('dashboard.submissions.resolution-letter', $submission))
            ->assertRedirect();
    }

    public function test_org_cannot_permanently_reject_via_org_review_endpoint(): void
    {
        $student = User::factory()->org()->create();
        $submission = Submission::factory()->awaitingOrgApproval()->create(['user_id' => $student->id]);

        $this->actingAs($student)
            ->postJson('/api/submissions/'.$submission->id.'/org-review/reject', [
                'notes' => 'This should not be a permanent rejection.',
                'action' => 'reject',
            ])
            ->assertForbidden();

        $this->assertNotSame('rejected', $submission->fresh()->workflow_status);
    }

    public function test_department_evaluate_page_shows_reject_inline_beside_save(): void
    {
        $dept = $this->department('School of Engineering');
        $reviewer = User::factory()->department()->create(['department_id' => $dept->id]);
        $student = User::factory()->org()->create(['department_id' => $dept->id]);
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => $dept->id,
        ]);

        $html = $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.evaluate', $submission))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="rejectButton"', $html);
        $this->assertLessThan(
            strpos($html, 'id="rejectButton"'),
            strpos($html, 'id="saveButton"')
        );
    }

    public function test_rejected_submission_cannot_be_undone_by_any_endpoint(): void
    {
        $admin = User::factory()->admin()->create();
        $pair = User::factory()->pair()->create();
        $student = User::factory()->org()->create();
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => null,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/submissions/'.$submission->id.'/transition', ['status' => 'rejected', 'notes' => 'Rejected by admin for policy reasons.'])
            ->assertOk();

        $this->assertSame('rejected', $submission->fresh()->workflow_status);

        foreach (['approved', 'revised', 'under_peer_review', 'posted'] as $status) {
            $this->actingAs($admin)
                ->postJson('/api/submissions/'.$submission->id.'/transition', ['status' => $status])
                ->assertStatus(422);
        }

        $this->actingAs($pair)
            ->postJson('/api/submissions/'.$submission->id.'/save-manual-caption', [
                'manual_caption' => 'Trying to reopen a rejected submission.',
            ])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->putJson('/api/submissions/'.$submission->id.'/approve')
            ->assertStatus(422);

        $this->actingAs($student)
            ->postJson('/api/submissions/'.$submission->id.'/org-review/approve', [])
            ->assertStatus(422);

        $this->actingAs($student)
            ->postJson('/api/submissions/'.$submission->id.'/org-review/reject', [
                'notes' => 'Requesting revisions on a rejected one.',
                'action' => 'revision',
            ])
            ->assertStatus(422);

        $this->assertSame('rejected', $submission->fresh()->workflow_status);
    }

    public function test_rejected_submission_is_view_only_with_no_action_buttons(): void
    {
        $admin = User::factory()->admin()->create();
        $pair = User::factory()->pair()->create();
        $student = User::factory()->org()->create();
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => null,
            'original_caption' => 'Original caption kept visible after rejection',
            'workflow_status' => 'rejected',
        ]);

        foreach ([$admin, $pair] as $staff) {
            $this->actingAs($staff)
                ->get(route('dashboard.submissions.evaluate', $submission))
                ->assertRedirect(route('dashboard.submissions.review', $submission));

            $this->actingAs($staff)
                ->get(route('dashboard.submissions.review', $submission))
                ->assertOk()
                ->assertSee('Original caption kept visible after rejection')
                ->assertSee('view only')
                ->assertDontSee('id="lifecycleSteps-'.$submission->id.'"', false)
                ->assertDontSee('Enhance Caption');

            $this->actingAs($staff)
                ->get(route('dashboard.submissions'))
                ->assertOk()
                ->assertDontSee(route('dashboard.submissions.evaluate', $submission), false);
        }

        $this->actingAs($student)
            ->get(route('org.submissions.review', $submission))
            ->assertOk()
            ->assertSee('Original caption kept visible after rejection')
            ->assertDontSee('New Submission')
            ->assertDontSee('orgSubmitBtn', false);
    }

    public function test_published_and_posted_submission_cannot_be_undone_by_any_endpoint(): void
    {
        $admin = User::factory()->admin()->create();
        $pair = User::factory()->pair()->create();
        $student = User::factory()->org()->create();
        $submission = Submission::factory()->create([
            'user_id' => $student->id,
            'workflow_status' => 'posted',
            'status' => 'approved',
            'posted_at' => now()->subDay(),
        ]);

        foreach (['approved', 'revised', 'under_peer_review', 'rejected', 'submitted'] as $status) {
            $this->actingAs($admin)
                ->postJson('/api/submissions/'.$submission->id.'/transition', ['status' => $status])
                ->assertStatus(422)
                ->assertJsonFragment([
                    'message' => 'This submission has been published and posted. It is view only and cannot be undone.',
                ]);
        }

        $this->actingAs($pair)
            ->postJson('/api/submissions/'.$submission->id.'/save-manual-caption', [
                'manual_caption' => 'Trying to edit a posted submission.',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'This submission has been published and posted. It is view only and cannot be undone.',
            ]);

        $this->actingAs($admin)
            ->putJson('/api/submissions/'.$submission->id.'/approve')
            ->assertStatus(422);

        $this->actingAs($student)
            ->postJson('/api/submissions/'.$submission->id.'/org-review/approve', [])
            ->assertStatus(422);

        $this->actingAs($student)
            ->postJson('/api/submissions/'.$submission->id.'/org-review/reject', [
                'notes' => 'Requesting revisions on a posted one.',
                'action' => 'revision',
            ])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->deleteJson('/api/submissions/'.$submission->id)
            ->assertStatus(422);

        $this->assertSame('posted', $submission->fresh()->workflow_status);
    }

    public function test_published_and_posted_submission_is_view_only_with_no_action_buttons(): void
    {
        $admin = User::factory()->admin()->create();
        $pair = User::factory()->pair()->create();
        $student = User::factory()->org()->create();
        $submission = Submission::factory()->create([
            'user_id' => $student->id,
            'review_department_id' => null,
            'original_caption' => 'Original caption of published content',
            'workflow_status' => 'posted',
            'status' => 'approved',
            'posted_at' => now()->subHour(),
        ]);

        foreach ([$admin, $pair] as $staff) {
            $this->actingAs($staff)
                ->get(route('dashboard.submissions.evaluate', $submission))
                ->assertRedirect(route('dashboard.submissions.review', $submission));

            $this->actingAs($staff)
                ->get(route('dashboard.submissions.review', $submission))
                ->assertOk()
                ->assertSee('Original caption of published content')
                ->assertSee('view only')
                ->assertDontSee('id="lifecycleSteps-'.$submission->id.'"', false)
                ->assertDontSee('Evaluate &amp; edit', false)
                ->assertDontSee('Enhance Caption');

            $this->actingAs($staff)
                ->get(route('dashboard.submissions'))
                ->assertOk()
                ->assertDontSee(route('dashboard.submissions.evaluate', $submission), false)
                ->assertSee('View');
        }

        $this->actingAs($student)
            ->get(route('org.submissions.review', $submission))
            ->assertOk()
            ->assertSee('Original caption of published content')
            ->assertSee('view only and cannot be undone')
            ->assertDontSee('orgSubmitBtn', false);
    }
}
