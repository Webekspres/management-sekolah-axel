<?php

use App\Models\AcademicYear;
use App\Models\LessonPlan;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * Expose the private ensureDemoUserProfiles() method for testing.
 */
function makeTestSeeder(): DatabaseSeeder
{
    return new class extends DatabaseSeeder
    {
        public function runEnsureDemoUserProfiles(): void
        {
            $this->ensureDemoUserProfiles();
        }
    };
}

test('ensureDemoUserProfiles membuat Teacher untuk Guru Demo jika belum ada', function () {
    $guruUser = User::factory()->create([
        'email' => 'guru@example.com',
        'role' => 'guru',
        'password' => Hash::make('guru123'),
    ]);

    Level::factory()->create(['name' => 'SMP']);
    AcademicYear::factory()->create(['is_active' => true]);
    Subject::factory()->create();

    expect(Teacher::query()->where('user_id', $guruUser->id)->exists())->toBeFalse();

    makeTestSeeder()->runEnsureDemoUserProfiles();

    expect(Teacher::query()->where('user_id', $guruUser->id)->exists())->toBeTrue();
});

test('ensureDemoUserProfiles membuat SchoolClass, Schedule, dan LessonPlan untuk Guru Demo', function () {
    $guruUser = User::factory()->create([
        'email' => 'guru@example.com',
        'role' => 'guru',
        'password' => Hash::make('guru123'),
    ]);

    Level::factory()->create(['name' => 'SMP']);
    AcademicYear::factory()->create(['is_active' => true]);
    Subject::factory()->create();

    makeTestSeeder()->runEnsureDemoUserProfiles();

    $teacher = Teacher::query()->where('user_id', $guruUser->id)->firstOrFail();

    expect(SchoolClass::query()->where('teacher_id', $teacher->id)->exists())->toBeTrue();
    expect(Schedule::query()->where('teacher_id', $teacher->id)->exists())->toBeTrue();
    expect(LessonPlan::query()->where('teacher_id', $teacher->id)->where('status', 'APPROVED')->exists())->toBeTrue();
});

test('ensureDemoUserProfiles tidak duplikasi Teacher jika sudah ada', function () {
    $guruUser = User::factory()->create([
        'email' => 'guru@example.com',
        'role' => 'guru',
        'password' => Hash::make('guru123'),
    ]);

    Level::factory()->create(['name' => 'SMP']);
    AcademicYear::factory()->create(['is_active' => true]);
    Subject::factory()->create();
    Teacher::factory()->create(['user_id' => $guruUser->id]);

    makeTestSeeder()->runEnsureDemoUserProfiles();

    expect(Teacher::query()->where('user_id', $guruUser->id)->count())->toBe(1);
});

test('ensureDemoUserProfiles membuat Student untuk Siswa Demo jika belum ada', function () {
    $siswaUser = User::factory()->create([
        'email' => 'siswa@example.com',
        'role' => 'siswa_ortu',
        'password' => Hash::make('siswa123'),
    ]);

    $class = SchoolClass::factory()->create();

    expect(Student::query()->where('user_id', $siswaUser->id)->exists())->toBeFalse();

    makeTestSeeder()->runEnsureDemoUserProfiles();

    expect(Student::query()->where('user_id', $siswaUser->id)->exists())->toBeTrue();

    $student = Student::query()->where('user_id', $siswaUser->id)->first();
    expect($student->class_id)->toBe($class->id);
});

test('ensureDemoUserProfiles tidak duplikasi Student jika sudah ada', function () {
    $siswaUser = User::factory()->create([
        'email' => 'siswa@example.com',
        'role' => 'siswa_ortu',
        'password' => Hash::make('siswa123'),
    ]);

    $class = SchoolClass::factory()->create();
    Student::factory()->create(['user_id' => $siswaUser->id, 'class_id' => $class->id]);

    makeTestSeeder()->runEnsureDemoUserProfiles();

    expect(Student::query()->where('user_id', $siswaUser->id)->count())->toBe(1);
});

test('ensureDemoUserProfiles tidak error jika email demo tidak ada di database', function () {
    expect(fn () => makeTestSeeder()->runEnsureDemoUserProfiles())->not->toThrow(Exception::class);
});

test('ensureDemoUserProfiles tidak membuat Student jika tidak ada kelas', function () {
    $siswaUser = User::factory()->create([
        'email' => 'siswa@example.com',
        'role' => 'siswa_ortu',
        'password' => Hash::make('siswa123'),
    ]);

    makeTestSeeder()->runEnsureDemoUserProfiles();

    expect(Student::query()->where('user_id', $siswaUser->id)->exists())->toBeFalse();
});
