<?php

namespace Tests\Feature;

use App\Support\AppTime;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AppTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_philippines_local_times_round_trip_through_utc_storage(): void
    {
        config(['app.display_timezone' => 'Asia/Manila']);

        $utcTime = AppTime::parseLocal('2026-11-20T08:30');

        $this->assertSame('2026-11-20 00:30:00', $utcTime->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-20 08:30', AppTime::format($utcTime, 'Y-m-d H:i'));
    }

    public function test_datetime_with_an_explicit_offset_is_converted_to_utc(): void
    {
        config(['app.display_timezone' => 'Asia/Manila']);

        $utcTime = AppTime::parseLocal('2026-11-20T08:30:00+08:00');

        $this->assertSame('2026-11-20 00:30:00', $utcTime->format('Y-m-d H:i:s'));
    }

    public function test_formatted_dates_display_utc_values_in_philippines_time(): void
    {
        config(['app.display_timezone' => 'Asia/Manila']);
        $utcTime = Carbon::createFromFormat('Y-m-d H:i:s', '2026-11-30 16:30:00', 'UTC');

        $this->blade('<x-formatted-date :at="$utcTime" format="M d, Y h:i A" />', ['utcTime' => $utcTime])
            ->assertSeeText('Dec 01, 2026 12:30 AM');
    }

    public function test_calendar_uses_philippines_day_boundaries_for_utc_stored_schedules(): void
    {
        config(['app.display_timezone' => 'Asia/Manila']);
        $admin = User::factory()->admin()->create();
        $submission = Submission::factory()->create([
            'workflow_status' => 'approved',
            'status' => 'approved',
            'scheduled_post_at' => Carbon::createFromFormat('Y-m-d H:i:s', '2026-11-30 16:30:00', 'UTC'),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/submissions/calendar?year=2026&month=12')
            ->assertOk()
            ->assertJsonPath('days.1.count', 1);

        $this->getJson('/api/submissions/calendar/day?date=2026-12-01')
            ->assertOk()
            ->assertJsonPath('posts.0.id', $submission->id)
            ->assertJsonPath('posts.0.scheduled_at', 'Dec 1, 2026 12:30 AM');
    }
}
