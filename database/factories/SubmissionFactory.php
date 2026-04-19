<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'under_review', 'approved'];
        
        return [
            'user_id' => User::factory(),
            'original_caption' => $this->faker->paragraphs(2, true),
            'enhanced_caption' => $this->faker->boolean(70) ? $this->faker->paragraphs(2, true) : null,
            'links' => $this->faker->boolean(60) ? [
                $this->faker->url(),
                $this->faker->url(),
            ] : null,
            'media_paths' => $this->faker->boolean(80) ? [
                'uploads/media/' . $this->faker->uuid() . '.jpg',
                'uploads/media/' . $this->faker->uuid() . '.jpg',
            ] : null,
            'status' => $this->faker->randomElement($statuses),
        ];
    }

    /**
     * Indicate that the submission is pending review.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'enhanced_caption' => null,
        ]);
    }

    /**
     * Indicate that the submission is under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
        ]);
    }

    /**
     * Indicate that the submission is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'enhanced_caption' => $this->faker->paragraphs(2, true),
        ]);
    }
}
