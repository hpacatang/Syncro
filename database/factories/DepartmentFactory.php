<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $name = fake()->unique()->city().' School';

        return [
            'department_name' => $name,
            'department_short_name' => strtoupper(fake()->unique()->lexify('???')),
        ];
    }
}
