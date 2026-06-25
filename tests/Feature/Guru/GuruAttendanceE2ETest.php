<?php

use App\Filament\Guru\Resources\Kbms\Pages\InputKbmAttendance;
use App\Filament\Guru\Widgets\GuruTodayChecklistTable;
use App\Models\AcademicYear;
use App\Models\Kbm;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('guru'));
});

test('e2e guru isi absensi menampilkan siswa di kelas KBM', function () {
    $user = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $user->id]);
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $teacher->id]);
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);
    $students = Student::factory(2)->create(['class_id' => $schoolClass->id]);

    actingAs($user);

    Livewire::test(InputKbmAttendance::class, ['record' => $kbm->getRouteKey()])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($students)
        ->assertDontSee('Tidak ada siswa di kelas ini');
});

test('e2e dashboard checklist isi absensi mengarah ke halaman input KBM', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-25 09:00:00'));

    $user = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $user->id]);
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $teacher->id]);
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
        'day_of_week' => now()->dayOfWeekIso,
    ]);
    $kbm = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'date' => today()->toDateString(),
    ]);
    Student::factory()->create(['class_id' => $schoolClass->id]);

    actingAs($user);

    Livewire::test(GuruTodayChecklistTable::class)
        ->assertSuccessful()
        ->assertTableActionExists('absensi', record: $schedule);

    Livewire::test(InputKbmAttendance::class, ['record' => $kbm->getRouteKey()])
        ->assertSuccessful()
        ->assertDontSee('Tidak ada siswa di kelas ini');
});

test('e2e demo seeder menempatkan siswa di kelas guru demo', function () {
    $level = Level::factory()->create();
    AcademicYear::factory()->active()->create();
    Subject::factory()->create(['level_id' => $level->id]);

    User::factory()->asGuru()->create(['email' => 'guru@example.com']);
    User::factory()->asSiswa()->create(['email' => 'siswa@example.com']);

    $method = new ReflectionMethod(DatabaseSeeder::class, 'ensureDemoUserProfiles');
    $method->setAccessible(true);
    $method->invoke(new DatabaseSeeder);

    $guruDemo = User::query()->where('email', 'guru@example.com')->firstOrFail();
    $demoClass = SchoolClass::query()
        ->where('teacher_id', $guruDemo->teacher?->id)
        ->first();

    expect($demoClass)->not->toBeNull();

    $studentCount = Student::withoutGlobalScopes()
        ->where('class_id', $demoClass->id)
        ->count();

    expect($studentCount)->toBeGreaterThan(0);

    $siswaDemo = User::query()->where('email', 'siswa@example.com')->firstOrFail();

    expect($siswaDemo->student?->class_id)->toBe($demoClass->id);
});

test('e2e guru tanpa siswa di kelas melihat empty state informatif', function () {
    $user = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $user->id]);
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $teacher->id]);
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);

    actingAs($user);

    Livewire::test(InputKbmAttendance::class, ['record' => $kbm->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Tidak ada siswa di kelas ini');
});
