<?php

namespace Tests\Feature;

use App\Models\Media;
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

    public function test_staff_dashboard_does_not_check_remote_storage_while_rendering_media_previews(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $org = User::factory()->create(['role' => 'org']);
        Submission::factory()->submitted()->create([
            'user_id' => $org->id,
            'media_paths' => ['submissions/media/remote-photo.jpg'],
        ]);
        Storage::shouldReceive('disk')->never();

        $this->actingAs($pair)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('submission-media/submissions/media/remote-photo.jpg', false);
    }

    public function test_media_gallery_does_not_check_remote_storage_for_every_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $org = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->submitted()->create(['user_id' => $org->id]);
        Media::factory()->submissionAttachment($submission)->create();
        Storage::shouldReceive('disk')->never();

        $this->actingAs($admin)
            ->get(route('staff.media-gallery'))
            ->assertOk()
            ->assertSee('Media gallery', false);
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

    public function test_guest_auth_pages_use_built_local_assets_instead_of_cdns(): void
    {
        foreach ([route('login'), route('register')] as $route) {
            $this->get($route)
                ->assertOk()
                ->assertSee('/build/assets/vendor-icons-', false)
                ->assertSee('/build/assets/app-', false)
                ->assertDontSee('cdnjs.cloudflare.com')
                ->assertDontSee('cdn.jsdelivr.net');
        }
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

    public function test_org_user_is_redirected_from_staff_dashboard(): void
    {
        $org = User::factory()->create(['role' => 'org']);

        $this->actingAs($org)
            ->get('/dashboard')
            ->assertRedirect(route('org.dashboard'));
    }

    public function test_under_pair_review_banner_shows_for_org_not_staff(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $org = User::factory()->create(['role' => 'org']);
        $submission = Submission::factory()->awaitingOrgApproval()->create(['user_id' => $org->id]);

        $banner = 'our team is reviewing your submission';

        $this->actingAs($pair)
            ->get(route('dashboard.submissions.review', $submission))
            ->assertOk()
            ->assertDontSee($banner, false);

        $this->actingAs($org)
            ->get(route('org.submissions.review', $submission))
            ->assertOk()
            ->assertSee($banner, false);
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

    public function test_submissions_table_paginates_at_ten_items(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $org = User::factory()->create(['role' => 'org']);
        Submission::factory()->count(12)->create(['user_id' => $org->id]);

        $response = $this->actingAs($pair)
            ->get(route('dashboard.submissions'))
            ->assertOk();

        // Verifies view receives paginated submissions with 10 per page
        $paginated = $response->viewData('submissions');
        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(12, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
        $this->assertTrue($paginated->hasPages());

        // Verifies page link is present in the rendered HTML
        $response->assertSee('page=2', false);
    }

    public function test_rejected_badge_renders_text_without_x_icon(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $org = User::factory()->create(['role' => 'org']);
        $rejected = Submission::factory()->rejected()->create(['user_id' => $org->id]);

        $this->actingAs($pair)
            ->get(route('dashboard.submissions'))
            ->assertOk()
            ->assertSee('Rejected')
            ->assertDontSee('bi-x-circle');
    }

    public function test_staff_dashboard_paginates_at_ten_items(): void
    {
        $pair = User::factory()->create(['role' => 'pair']);
        $org = User::factory()->create(['role' => 'org']);
        Submission::factory()->count(12)->create(['user_id' => $org->id]);

        $response = $this->actingAs($pair)
            ->get(route('dashboard'))
            ->assertOk();

        $paginated = $response->viewData('submissions');
        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(12, $paginated->total());
        $this->assertTrue($paginated->hasPages());
        $response->assertSee('page=2', false);
    }

    public function test_org_dashboard_paginates_at_ten_items(): void
    {
        $org = User::factory()->create(['role' => 'org']);
        Submission::factory()->count(12)->create(['user_id' => $org->id]);

        $response = $this->actingAs($org)
            ->get(route('org.dashboard'))
            ->assertOk();

        $paginated = $response->viewData('submissions');
        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(12, $paginated->total());
        $this->assertTrue($paginated->hasPages());
        $response->assertSee('page=2', false);
    }
}


