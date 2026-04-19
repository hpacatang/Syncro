<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(['user', 'pair', 'org', 'admin']),
        ];
    }

    /**
     * Indicate the user is a PAIR staff member.
     */
    public function pair(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'pair',
        ]);
    }

    /**
     * Indicate the user is from an organization/department.
     */
    public function org(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'org',
        ]);
    }

    /**
     * Indicate the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
