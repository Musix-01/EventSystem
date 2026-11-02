<?php

namespace Database\Factories;

use App\Enums\Organization;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_number' => fake()->unique()->numerify('2024-#####') . '-SR-0',
            'name'           => fake()->name(),
            'organization'   => fake()->randomElement(Organization::values()),
            'role'           => Role::Student->value,
            'password'       => Hash::make('password'),
        ];
    }
}
