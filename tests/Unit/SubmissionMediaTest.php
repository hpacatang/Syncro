<?php

namespace Tests\Unit;

use App\Support\SubmissionMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_and_serves_public_disk_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('submissions/media/sample.jpg', 'fake-image');

        $this->assertNotNull(SubmissionMedia::resolve('submissions/media/sample.jpg'));
        $this->assertStringContainsString('submission-media', SubmissionMedia::url('submissions/media/sample.jpg') ?? '');
        $this->assertTrue(SubmissionMedia::isImage('submissions/media/sample.jpg'));
    }
}
