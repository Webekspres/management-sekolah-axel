<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Referensi data independen ---
        $academicYear = AcademicYear::factory()->active()->create([
            'name' => '2025/2026',
            'semester' => 'Genap',
        ]);

        $levels = Level::factory(3)->create();

        // --- Admin & kepala sekolah ---
        User::factory()->asAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'gender' => 'L',
        ]);

        User::factory()->asKepalaSekolah()->create([
            'name' => 'Kepala Sekolah',
            'email' => 'kepsek@example.com',
            'password' => Hash::make('kepsek123'),
            'gender' => 'L',
        ]);

        // --- Guru: buat User dulu, lalu Teacher dari user yang sudah ada ---
        $guruUsers = User::factory(10)->asGuru()->create();

        $guruUsers->each(function (User $user) {
            Teacher::factory()->create(['user_id' => $user->id]);
        });

        $teachers = Teacher::all();

        // --- Kelas: pakai teacher & level yang sudah ada ---
        $classes = collect();
        $teachers->take(10)->each(function (Teacher $teacher) use ($levels, $academicYear, &$classes) {
            $class = SchoolClass::factory()->create([
                'teacher_id' => $teacher->id,
                'level_id' => $levels->random()->id,
                'academic_year_id' => $academicYear->id,
            ]);
            $classes->push($class);
        });

        // --- Student: buat User dulu, lalu Student dari user yang sudah ada ---
        $classes->each(function (SchoolClass $class) {
            $siswaUsers = User::factory(15)->asSiswa()->create();

            $siswaUsers->each(function (User $user) use ($class) {
                Student::factory()->create([
                    'user_id' => $user->id,
                    'class_id' => $class->id,
                ]);
            });
        });
    }
}
