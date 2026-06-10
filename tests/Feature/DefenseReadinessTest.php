<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefenseReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_all_staff_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $org = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $org->id]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.submissions'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.notifications'))->assertOk();
        $this->actingAs($admin)->get(route('staff.media-gallery'))->assertOk();
        $this->actingAs($admin)->get(route('staff.caption-assist'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.submissions.review', $submission))->assertOk();
    }

    public function test_pair_can_open_staff_workspace(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $org = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $org->id]);

        $this->actingAs($pair)->get(route('dashboard'))->assertOk();
        $this->actingAs($pair)->get(route('dashboard.submissions'))->assertOk();
        $this->actingAs($pair)->get(route('dashboard.submissions.review', $submission))->assertOk();
        $this->actingAs($pair)->get(route('staff.media-gallery'))->assertOk();
    }

    public function test_org_can_open_all_org_pages(): void
    {
        $org = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $org->id]);

        $this->actingAs($org)->get(route('org.dashboard'))->assertOk();
        $this->actingAs($org)->get(route('org.submit'))->assertOk();
        $this->actingAs($org)->get(route('org.submissions'))->assertOk();
        $this->actingAs($org)->get(route('org.notifications'))->assertOk();
        $this->actingAs($org)->get(route('org.submissions.review', $submission))->assertOk();
    }

    public function test_cross_role_navigation_redirects_smoothly(): void
    {
        $org = User::factory()->create(['role' => 'org']);
        $pair = User::factory()->create(['role' => 'pair']);

        $this->actingAs($org)->get('/dashboard')->assertRedirect(route('org.dashboard'));
        $this->actingAs($org)->get('/dashboard/media-gallery')->assertRedirect(route('org.dashboard'));
        $this->actingAs($pair)->get('/org/dashboard')->assertRedirect(route('dashboard'));
    }

    public function test_org_cannot_open_another_orgs_review_page(): void
    {
        $orgA = User::factory()->create(['role' => 'org']);
        $orgB = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $orgB->id]);

        $this->actingAs($orgA)
            ->get(route('org.submissions.review', $submission))
            ->assertNotFound();
    }
}
