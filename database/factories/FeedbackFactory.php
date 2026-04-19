<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $feedbackMessages = [
            'Please revise the caption to be more formal.',
            'Great submission! Minor adjustments needed for brand consistency.',
            'Can you provide a higher resolution image for this submission?',
            'The tone is perfect. Approved for posting.',
            'Please add event date and location to the caption.',
            'This looks good but needs one more image for the gallery.',
            'Consider making the text more concise and impactful.',
            'Approved with the suggested enhancements applied.',
            'Please resubmit with updated information.',
            'Caption needs to match the brand voice guidelines.',
            'Excellent work! Ready to schedule for posting.',
            'Can you clarify the call-to-action in this caption?',
        ];

        return [
            'submission_id' => Submission::factory(),
            'user_id' => User::factory(),
            'message' => $this->faker->randomElement($feedbackMessages),
        ];
    }

    /**
     * Indicate feedback is from a PAIR staff member.
     */
    public function fromPair(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->create(['role' => 'pair']),
        ]);
    }

    /**
     * Indicate feedback is from an organization.
     */
    public function fromOrg(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->create(['role' => 'org']),
        ]);
    }
}
