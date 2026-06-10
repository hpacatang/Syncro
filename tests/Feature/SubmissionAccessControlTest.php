<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_api_index_returns_only_own_submissions(): void
    {
        $orgA = User::factory()->create(['role' => 'org']);
        $orgB = User::factory()->create(['role' => 'org']);
        Submission::factory()->submitted()->create(['user_id' => $orgA->id]);
        Submission::factory()->submitted()->create(['user_id' => $orgB->id]);

        $response = $this->actingAs($orgA)->getJson('/api/submissions');

        $response->assertOk();
        $this->assertSame(1, $response->json('count'));
        $this->assertSame($orgA->id, $response->json('data.0.user_id'));
    }

    public function test_staff_api_index_returns_all_submissions(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $orgA = User::factory()->create(['role' => 'org']);
        $orgB = User::factory()->create(['role' => 'org']);
        Submission::factory()->submitted()->create(['user_id' => $orgA->id]);
        Submission::factory()->submitted()->create(['user_id' => $orgB->id]);

        $response = $this->actingAs($pair)->getJson('/api/submissions');

        $response->assertOk();
        $this->assertSame(2, $response->json('count'));
    }

    public function test_org_cannot_view_other_org_submission(): void
    {
        $orgA = User::factory()->create(['role' => 'org']);
        $orgB = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $orgB->id]);

        $this->actingAs($orgA)
            ->getJson('/api/submissions/'.$submission->id)
            ->assertNotFound();
    }

    public function test_org_cannot_call_staff_transition_endpoint(): void
    {
        $org = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $org->id]);

        $this->actingAs($org)
            ->postJson('/api/submissions/'.$submission->id.'/transition', ['status' => 'under_peer_review'])
            ->assertForbidden();
    }

    
    public function test_staff_cannot_manually_transition_to_under_peer_review(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $org  = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $org->id]);

        $this->actingAs($pair)
            ->postJson('/api/submissions/'.$submission->id.'/transition', ['status' => 'under_peer_review'])
            ->assertStatus(422); 
    }

    
    public function test_staff_can_approve_submission_under_peer_review(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $org  = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->awaitingOrgApproval()->create(['user_id' => $org->id]);

        $this->actingAs($pair)
            ->postJson('/api/submissions/'.$submission->id.'/transition', ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.workflow_status', 'approved');
    }
}

