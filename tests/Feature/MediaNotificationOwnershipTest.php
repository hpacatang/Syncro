<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaNotificationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_uploads_are_linked_to_the_uploader_and_submission(): void
    {
        Storage::fake('public');
        config(['filesystems.disks.supabase.key' => null]);

        $department = Department::factory()->create();
        $student = User::factory()->studentInDepartment()->create([
            'department_id' => $department->id,
        ]);
        $pair = User::factory()->pair()->create();

        $submission = $this->actingAs($student)
            ->postJson('/api/submissions', [
                'original_caption' => 'A student organization announcement.',
                'review_destination' => 'pair',
                'media' => [UploadedFile::fake()->image('event-photo.jpg')],
            ])
            ->assertCreated()
            ->json('data');

        $media = Media::query()->firstOrFail();
        $this->assertSame($student->id, $media->uploaded_by);
        $this->assertSame($submission['id'], $media->submission_id);
        $this->assertSame('event-photo.jpg', $media->original_name);
        $this->assertSame($student->id, $media->uploader->id);
        $this->assertSame($submission['id'], $media->submission->id);
        $this->assertSame($media->id, $student->uploadedMedia()->sole()->id);
        Storage::disk('public')->assertExists($media->path);

        $pairNotification = $pair->notifications()->sole();
        $this->assertSame($pair->id, $pairNotification->notifiable_id);
        $this->assertSame(User::class, $pairNotification->notifiable_type);
        $this->assertSame($pair->id, $pairNotification->notifiable->id);
        $this->assertCount(0, $student->notifications);
    }

}
