<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use App\Support\SubmissionMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_renders_with_submissions(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $org = User::factory()->create(['role' => 'org']);
        Submission::factory()->submitted()->create(['user_id' => $org->id]);
        Submission::factory()->awaitingOrgApproval()->create(['user_id' => $org->id]);
        Submission::factory()->approved()->create([
            'user_id' => $org->id,
            'workflow_status' => 'approved',
        ]);

        $this->actingAs($pair)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Content Queue', false);
    }

    public function test_org_dashboard_renders_with_null_timestamps(): void
    {
        $org = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $org->id]);

        DB::table('submissions')->where('id', $submission->id)->update([
            'created_at' => null,
            'updated_at' => null,
        ]);

        $this->actingAs($org)
            ->get(route('org.dashboard'))
            ->assertOk()
            ->assertSee('Your Submissions', false);
    }

    public function test_lifecycle_updates_route_is_not_shadowed_by_submission_show(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);

        $this->actingAs($pair)
            ->getJson('/api/submissions/lifecycle-updates')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_staff_submissions_list_page_renders(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);

        $this->actingAs($pair)
            ->get(route('dashboard.submissions'))
            ->assertOk();
    }

    public function test_submission_media_route_serves_uploaded_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('submissions/media/photo.jpg', 'binary-image');

        $pair = User::factory()->create(['role' => 'pair']);

        $this->actingAs($pair)
            ->get(SubmissionMedia::url('submissions/media/photo.jpg'))
            ->assertOk();
    }
}
