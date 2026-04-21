<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\Teacher;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'gender' => 'L',
            'role' => 'super_admin',
        ]);
        Teacher::factory(10)->create();
        Classes::factory(10)->create();
    }
}
