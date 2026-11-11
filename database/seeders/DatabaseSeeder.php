<?php

namespace Database\Seeders;

use App\Enums\Organization;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin, PUP office.
        User::create([
            'name'           => 'PUP Admin',
            'email'          => 'admin@pup.edu.ph',
            'student_number' => null,
            'organization'   => null,
            'role'           => Role::Admin->value,
            'password'       => 'password',
        ]);

        // Faculty
        User::create([
            'name'           => 'Test Faculty',
            'email'          => 'faculty@pup.edu.ph',
            'student_number' => null,
            'organization'   => null,
            'role'           => Role::Faculty->value,
            'password'       => 'password',
        ]);

        // Student org leader
        User::create([
            'name'           => 'Test ELITE Officer',
            'student_number' => '2024-00001-SR-0',
            'organization'   => Organization::ELITE->value,
            'role'           => Role::Officer->value,
            'password'       => 'password',
        ]);

        // Regular student.
        User::create([
            'name'           => 'Test ELITE Student',
            'student_number' => '2024-00002-SR-0',
            'organization'   => Organization::ELITE->value,
            'role'           => Role::Student->value,
            'password'       => 'password',
        ]);
    }
}
