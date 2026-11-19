<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MetaPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_meta_configuration_does_not_mark_a_submission_as_posted(): void
    {
        config([
            'services.meta.access_token' => 'configured-page-token',
            'services.meta.page_id' => null,
        ]);
        Http::fake();
        $admin = User::factory()->admin()->create();
        $submission = $this->approvedSocialSubmission();

        $this->actingAs($admin)
            ->postJson($this->transitionUrl($submission), [
                'status' => 'posted',
                'posted_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.workflow_status', 'approved');

        $submission->refresh();
        $this->assertSame('approved', $submission->workflow_status);
        $this->assertSame('failed', $submission->meta_publish_status);
        $this->assertStringContainsString('META_ACCESS_TOKEN and META_PAGE_ID', $submission->meta_publish_error);
        Http::assertNothingSent();
    }

    public function test_meta_api_failure_is_visible_and_can_be_retried_without_duplicate_success_state(): void
    {
        config([
            'services.meta.access_token' => 'page-token-for-test',
            'services.meta.page_id' => '123456789',
            'services.meta.graph_api_version' => 'v23.0',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Permission denied', 'code' => 200],
            ], 403),
        ]);

        $admin = User::factory()->admin()->create();
        $submission = $this->approvedSocialSubmission();

        $this->actingAs($admin)
            ->postJson($this->transitionUrl($submission), [
                'status' => 'posted',
                'posted_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Permission denied')
            ->assertJsonPath('data.meta_publish_status', 'failed');

        $submission->refresh();
        $this->assertSame('approved', $submission->workflow_status);
        $this->assertSame('failed', $submission->meta_publish_status);
        $this->assertSame(1, $submission->meta_publish_attempts);
    }

    public function test_successful_meta_publish_records_the_post_and_uses_a_bearer_token(): void
    {
        config([
            'services.meta.access_token' => 'page-token-for-test',
            'services.meta.page_id' => '123456789',
            'services.meta.graph_api_version' => 'v23.0',
        ]);
        Http::fake([
            'https://graph.facebook.com/v23.0/123456789/feed' => Http::response([
                'id' => '123456789_900',
            ]),
        ]);

        $admin = User::factory()->admin()->create();
        $submission = $this->approvedSocialSubmission();
        $response = $this->actingAs($admin)->postJson($this->transitionUrl($submission), [
            'status' => 'posted',
            'posted_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertOk()->assertJsonPath('data.workflow_status', 'posted');
        Http::assertSent(fn (ClientRequest $request) => $request->hasHeader(
            'Authorization',
            'Bearer page-token-for-test'
        ) && $request['message'] === $submission->original_caption);

        $submission->refresh();
        $this->assertSame('published', $submission->meta_publish_status);
        $this->assertSame('123456789_900', $submission->meta_post_id);
        $this->assertSame('https://www.facebook.com/123456789_900', $submission->meta_post_url);
        $this->assertSame('posted', $submission->workflow_status);
    }

    public function test_review_page_shows_the_configured_meta_destination_for_an_approved_social_post(): void
    {
        config([
            'services.meta.access_token' => 'page-token-for-test',
            'services.meta.page_id' => '123456789',
            'services.meta.page_name' => 'SyncroTest',
        ]);
        $admin = User::factory()->admin()->create();
        $submission = $this->approvedSocialSubmission();

        $this->actingAs($admin)
            ->get(route('dashboard.submissions.review', $submission))
            ->assertOk()
            ->assertSee('Destination: SyncroTest')
            ->assertSee('Publish / schedule')
            ->assertSee('one public image')
            ->assertSee('multi-image publishing are not implemented');
    }

    public function test_review_page_explains_when_attachment_mix_is_not_supported_by_meta_publishing(): void
    {
        config([
            'services.meta.access_token' => 'page-token-for-test',
            'services.meta.page_id' => '123456789',
        ]);
        $admin = User::factory()->admin()->create();
        $submission = $this->approvedSocialSubmission([
            'media_paths' => ['submissions/media/first.jpg', 'submissions/media/second.jpg'],
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.submissions.review', $submission))
            ->assertOk()
            ->assertSee('text-only posts or one public image')
            ->assertSee('this submission has 2 attachments')
            ->assertDontSee('Publish / schedule');
    }

    public function test_reviewer_can_schedule_an_approved_social_post_without_publishing_it_immediately(): void
    {
        config([
            'services.meta.access_token' => 'page-token-for-test',
            'services.meta.page_id' => '123456789',
        ]);
        Http::fake();
        $admin = User::factory()->admin()->create();
        $submission = $this->approvedSocialSubmission();
        $scheduledAt = now()->addDay()->startOfMinute();

        $this->actingAs($admin)
            ->postJson($this->transitionUrl($submission), [
                'status' => 'posted',
                'posted_at' => $scheduledAt->format('Y-m-d H:i:s'),
                'scheduled_post_at' => $scheduledAt->format('Y-m-d H:i:s'),
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertSee('scheduled');

        $submission->refresh();
        $this->assertSame('approved', $submission->workflow_status);
        $this->assertSame($scheduledAt->format('Y-m-d H:i'), $submission->scheduled_post_at->format('Y-m-d H:i'));
        Http::assertNothingSent();
    }

    public function test_meta_publishing_supports_one_public_image_attachment(): void
    {
        config([
            'services.meta.access_token' => 'page-token-for-test',
            'services.meta.page_id' => '123456789',
            'services.meta.graph_api_version' => 'v23.0',
            'filesystems.default' => 'supabase',
            'filesystems.disks.supabase.public_url' => 'https://storage.example/public',
        ]);
        Storage::fake('supabase');
        Storage::disk('supabase')->put('submissions/media/post.jpg', 'test-image');
        Http::fake([
            'https://graph.facebook.com/v23.0/123456789/photos' => Http::response([
                'id' => 'photo-100',
                'post_id' => '123456789_100',
            ]),
        ]);

        $admin = User::factory()->admin()->create();
        $submission = $this->approvedSocialSubmission([
            'media_paths' => ['submissions/media/post.jpg'],
        ]);

        $this->actingAs($admin)->postJson($this->transitionUrl($submission), [
            'status' => 'posted',
            'posted_at' => now()->format('Y-m-d H:i:s'),
        ])->assertOk();

        Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/photos')
            && $request['url'] === 'https://storage.example/public/submissions/media/post.jpg'
            && $request['published'] === true);
    }

    public function test_article_publish_is_rejected_until_a_cms_integration_is_configured(): void
    {
        $admin = User::factory()->admin()->create();
        $submission = Submission::factory()->create([
            'workflow_status' => 'approved',
            'status' => 'approved',
            'submission_type' => Submission::TYPE_WEBSITE_ARTICLE,
            'article_title' => 'University research event',
            'original_caption' => str_repeat('The university shared verified details about its research event. ', 3),
        ]);

        $this->actingAs($admin)
            ->postJson($this->transitionUrl($submission), [
                'status' => 'posted',
                'posted_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Website article publishing is not available until the university CMS API is configured.');

        $this->assertSame('approved', $submission->fresh()->workflow_status);
        Http::assertNothingSent();
    }

    private function approvedSocialSubmission(array $attributes = []): Submission
    {
        return Submission::factory()->create(array_merge([
            'workflow_status' => 'approved',
            'status' => 'approved',
            'submission_type' => Submission::TYPE_SOCIAL_MEDIA,
            'original_caption' => 'An approved university update for the Facebook Page.',
            'enhanced_caption' => null,
            'links' => null,
            'media_paths' => null,
        ], $attributes));
    }

    private function transitionUrl(Submission $submission): string
    {
        return "/api/submissions/{$submission->id}/transition";
    }
}
