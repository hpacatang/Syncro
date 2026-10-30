<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionQueuedForReview;
use App\Notifications\SubmissionUnderReview;
use App\Notifications\SubmissionApproved;
use App\Notifications\EnhancementReadyForOrg;
use App\Services\ContentSimilarityChecker;
use App\Support\SubmissionMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionEnhancementsAndDuplicateCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_include_sender_names_where_they_came_from(): void
    {
        $org = User::factory()->org()->create(['name' => 'SSC Organization', 'profile_name' => 'SSC Organization']);
        $reviewer = User::factory()->pair()->create(['name' => 'Jane PAIR Staff', 'profile_name' => 'Jane PAIR Staff']);

        $submission = Submission::factory()->submitted()->create([
            'user_id' => $org->id,
            'original_caption' => 'Testing notification sender attribution across the board.',
        ]);

        // 1. SubmissionQueuedForReview
        $queuedNotif = new SubmissionQueuedForReview($submission);
        $queuedData = $queuedNotif->toArray($reviewer);
        $this->assertArrayHasKey('from', $queuedData);
        $this->assertArrayHasKey('sender_name', $queuedData);
        $this->assertStringContainsString('SSC', $queuedData['sender_name']);

        // 2. SubmissionUnderReview
        $this->actingAs($reviewer);
        $underReviewNotif = new SubmissionUnderReview($submission);
        $underReviewData = $underReviewNotif->toArray($org);
        $this->assertArrayHasKey('from', $underReviewData);
        $this->assertArrayHasKey('sender_name', $underReviewData);
        $this->assertStringContainsString('Jane PAIR Staff', $underReviewData['sender_name']);

        // 3. EnhancementReadyForOrg
        $enhancementNotif = new EnhancementReadyForOrg($submission);
        $enhancementData = $enhancementNotif->toArray($org);
        $this->assertArrayHasKey('from', $enhancementData);
        $this->assertArrayHasKey('sender_name', $enhancementData);
        $this->assertStringContainsString('Jane PAIR Staff', $enhancementData['sender_name']);

        // 4. Notification API recent includes 'from'
        $org->notify($underReviewNotif);
        $this->actingAs($org);
        $response = $this->getJson(route('notifications.recent'));
        $response->assertOk();
        $notifs = $response->json('notifications');
        $this->assertNotEmpty($notifs);
        $this->assertArrayHasKey('from', $notifs[0]);
        $this->assertStringContainsString('Jane PAIR Staff', $notifs[0]['from']);
    }

    public function test_admin_and_pair_receive_notification_with_org_name_date_and_time(): void
    {
        $department = \App\Models\Department::factory()->create(['department_name' => 'College of Engineering']);
        $org = User::factory()->org()->create([
            'name' => 'Engg Council',
            'profile_name' => 'Engineering Student Council',
            'department_id' => $department->id,
        ]);
        $pairStaff = User::factory()->pair()->create();

        $submission = Submission::factory()->create([
            'user_id' => $org->id,
            'original_caption' => 'General assembly will be held at main auditorium on Friday.',
            'scheduled_post_at' => now()->addDays(2),
            'review_department_id' => null,
            'workflow_status' => 'submitted',
        ]);

        $notification = new SubmissionQueuedForReview($submission);
        $data = $notification->toArray($pairStaff);

        $this->assertStringContainsString('Engineering Student Council', $data['sender_org']);
        $this->assertStringContainsString('College of Engineering', $data['sender_org']);
        $this->assertArrayHasKey('submitted_at', $data);
        $this->assertArrayHasKey('scheduled_post_at', $data);
        $this->assertStringContainsString('Submitted by Engineering Student Council', $data['message']);

        // Check rendering in notifications page
        $pairStaff->notify($notification);
        $this->actingAs($pairStaff)
            ->get(route('dashboard.notifications'))
            ->assertOk()
            ->assertSee('Engineering Student Council')
            ->assertSee('From Org:')
            ->assertSee('Submitted:');
    }

    public function test_submission_accepts_scheduled_post_at_date_and_time(): void
    {
        $org = User::factory()->org()->create();
        $targetDate = now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->actingAs($org)->postJson('/api/submissions', [
            'original_caption' => 'Upcoming university event scheduled for next week.',
            'scheduled_post_at' => $targetDate,
            'review_destination' => 'pair',
        ]);

        $response->assertCreated();
        $submissionId = $response->json('data.id');

        $submission = Submission::find($submissionId);
        $this->assertNotNull($submission);
        $this->assertNotNull($submission->scheduled_post_at);
        $this->assertSame(
            now()->addDays(3)->format('Y-m-d H:i'),
            $submission->scheduled_post_at->format('Y-m-d H:i')
        );
    }

    public function test_submission_allows_pictures_and_videos(): void
    {
        Storage::fake('public');
        $org = User::factory()->org()->create();

        $imageFile = UploadedFile::fake()->image('banner.png', 800, 600);
        $videoFile = UploadedFile::fake()->create('event_clip.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($org)->postJson('/api/submissions', [
            'original_caption' => 'A wonderful celebration captured on photo and video clip.',
            'review_destination' => 'pair',
            'media' => [$imageFile, $videoFile],
        ]);

        $response->assertCreated();
        $submissionId = $response->json('data.id');
        $submission = Submission::find($submissionId);

        $this->assertCount(2, $submission->media_paths);
        $this->assertTrue(SubmissionMedia::isImage($submission->media_paths[0]));
        $this->assertTrue(SubmissionMedia::isVideo($submission->media_paths[1]));
    }

    public function test_duplicate_and_highly_similar_content_is_rejected(): void
    {
        $org = User::factory()->org()->create();

        // Initial submission
        $originalText = 'Annual sports festival will commence this Friday at the university main oval arena.';
        $this->actingAs($org)->postJson('/api/submissions', [
            'original_caption' => $originalText,
            'review_destination' => 'pair',
        ])->assertCreated();

        // 1. Exact duplicate attempt
        $exactResponse = $this->actingAs($org)->postJson('/api/submissions', [
            'original_caption' => $originalText,
            'review_destination' => 'pair',
        ]);

        $exactResponse->assertStatus(422);
        $exactResponse->assertJsonFragment([
            'error' => 'DuplicateContentDetected',
        ]);

        // 2. Highly similar content attempt (>80% similarity)
        $similarText = 'Annual sports festival will commence this Friday at the university main oval grounds.';
        $similarResponse = $this->actingAs($org)->postJson('/api/submissions', [
            'original_caption' => $similarText,
            'review_destination' => 'pair',
        ]);

        $similarResponse->assertStatus(422);
        $similarResponse->assertJsonFragment([
            'error' => 'DuplicateContentDetected',
        ]);
        $this->assertStringContainsString('similarity', $similarResponse->json('reason'));

        // 3. Different content passes
        $differentText = 'Enrollment for the second semester begins on Monday across all academic departments.';
        $differentResponse = $this->actingAs($org)->postJson('/api/submissions', [
            'original_caption' => $differentText,
            'review_destination' => 'pair',
        ]);

        $differentResponse->assertCreated();
    }

    public function test_similarity_checker_flags_previous_rejected_posts(): void
    {
        $rejectedPost = Submission::factory()->create([
            'original_caption' => 'University blood donation drive scheduled on November 10 at gym.',
            'workflow_status' => 'rejected',
            'status' => 'pending',
        ]);

        $result = ContentSimilarityChecker::check(
            'University blood donation drive scheduled on November 10 at gym center.'
        );

        $this->assertNotNull($result);
        $this->assertTrue($result['should_reject']);
        $this->assertSame($rejectedPost->id, $result['matched_submission_id']);
        $this->assertStringContainsString('previously rejected', $result['reason']);
    }

    public function test_admin_and_pair_can_mark_submission_as_posted_with_chosen_date_and_time(): void
    {
        $admin = User::factory()->admin()->create();
        $submission = Submission::factory()->create([
            'workflow_status' => 'approved',
            'status' => 'approved',
        ]);

        $chosenPostDate = '2026-11-05 14:30:00';

        $response = $this->actingAs($admin)->postJson("/api/submissions/{$submission->id}/transition", [
            'status' => 'posted',
            'posted_at' => $chosenPostDate,
            'scheduled_post_at' => $chosenPostDate,
            'notes' => 'Published on official university Facebook page.',
        ]);

        $response->assertOk();
        $submission->refresh();

        $this->assertSame('posted', $submission->workflow_status);
        $this->assertNotNull($submission->posted_at);
        $this->assertSame('2026-11-05 14:30', $submission->posted_at->format('Y-m-d H:i'));
        $this->assertStringContainsString('Published on official university Facebook page', $submission->pair_feedback ?? '');
    }

    public function test_admin_and_pair_can_edit_posted_date_and_time_in_case_of_error(): void
    {
        $pairStaff = User::factory()->pair()->create();
        $submission = Submission::factory()->create([
            'workflow_status' => 'posted',
            'status' => 'approved',
            'posted_at' => '2026-11-01 10:00:00',
        ]);

        // Error detected: correct the published date & time
        $correctedDate = '2026-11-02 09:15:00';

        $response = $this->actingAs($pairStaff)->postJson("/api/submissions/{$submission->id}/transition", [
            'status' => 'posted',
            'posted_at' => $correctedDate,
            'scheduled_post_at' => $correctedDate,
            'notes' => 'Corrected publishing timestamp after reschedule.',
        ]);

        $response->assertOk();
        $submission->refresh();

        $this->assertSame('posted', $submission->workflow_status);
        $this->assertSame('2026-11-02 09:15', $submission->posted_at->format('Y-m-d H:i'));
        $this->assertStringContainsString('Corrected publishing timestamp', $submission->pair_feedback ?? '');
    }

    public function test_post_date_cannot_be_edited_once_publication_time_has_arrived(): void
    {
        $admin = User::factory()->admin()->create();
        $submission = Submission::factory()->create([
            'workflow_status' => 'posted',
            'status' => 'approved',
            'posted_at' => now()->subHours(2), // Time has already arrived
        ]);

        $response = $this->actingAs($admin)->postJson("/api/submissions/{$submission->id}/transition", [
            'status' => 'posted',
            'posted_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Publication date and time cannot be edited because the scheduled publication time has arrived and the post is locked.',
        ]);
    }

    public function test_scheduled_submissions_automatically_publish_when_date_and_time_arrives(): void
    {
        $org = User::factory()->org()->create();
        $submission = Submission::factory()->create([
            'user_id' => $org->id,
            'workflow_status' => 'approved',
            'status' => 'approved',
            'scheduled_post_at' => now()->subMinutes(10), // Scheduled time has passed
            'original_caption' => 'Scheduled university update ready for publication.',
        ]);

        $published = \App\Services\SocialMediaPublisher::publishDueSubmissions();
        $this->assertSame(1, $published);

        $this->artisan('submissions:publish-scheduled')->assertSuccessful();

        $submission->refresh();
        $this->assertSame('posted', $submission->workflow_status);
        $this->assertNotNull($submission->posted_at);
        $this->assertStringContainsString('Published to Social Media', $submission->pair_feedback ?? '');
    }

    public function test_facebook_graph_api_called_when_key_is_provided(): void
    {
        config([
            'services.facebook.page_access_token' => 'fake-page-token-xyz',
            'services.facebook.page_id' => '987654321',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://graph.facebook.com/v19.0/987654321/feed' => \Illuminate\Support\Facades\Http::response([
                'id' => '987654321_1234567890',
            ], 200),
        ]);

        $admin = User::factory()->admin()->create();
        $submission = Submission::factory()->create([
            'workflow_status' => 'approved',
            'status' => 'approved',
            'original_caption' => 'Announcing campus open house this Saturday.',
            'enhanced_caption' => null,
        ]);

        $response = $this->actingAs($admin)->postJson("/api/submissions/{$submission->id}/transition", [
            'status' => 'posted',
            'posted_at' => now()->subMinute()->format('Y-m-d H:i:s'), // Time is past/now
        ]);

        $response->assertOk();

        \Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'https://graph.facebook.com/v19.0/987654321/feed'
                && $request['access_token'] === 'fake-page-token-xyz'
                && str_contains($request['message'], 'Announcing campus open house');
        });
    }
}
