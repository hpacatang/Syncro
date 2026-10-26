<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'uploaded_by' => User::factory()->org(),
            'submission_id' => null,
            'purpose' => 'profile_photo',
            'storage_disk' => 'public',
            'path' => 'profile-photos/'.$this->faker->uuid().'.jpg',
            'original_name' => $this->faker->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(1000, 5000000),
        ];
    }

    public function profilePhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_id' => null,
            'purpose' => 'profile_photo',
            'path' => 'profile-photos/'.$this->faker->uuid().'.jpg',
        ]);
    }

    public function submissionAttachment(Submission $submission): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_by' => $submission->user_id,
            'submission_id' => $submission->id,
            'purpose' => 'submission',
            'path' => 'submissions/media/'.$this->faker->uuid().'.jpg',
        ]);
    }
}
