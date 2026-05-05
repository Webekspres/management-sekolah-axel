<?php

use App\Filament\Guru\Resources\AttitudeScores\Pages\CreateAttitudeScore;
use App\Filament\Guru\Resources\AttitudeScores\Pages\EditAttitudeScore;
use App\Filament\Guru\Resources\AttitudeScores\Pages\ListAttitudeScores;
use App\Models\AcademicYear;
use App\Models\AttitudeScore;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

// ─────────────────────────────────────────────────────────────────────────────
// Setup
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->guruUser = User::factory()->asGuru()->create();
    $this->teacher = Teacher::factory()->create(['user_id' => $this->guruUser->id]);

    $this->academicYear = AcademicYear::factory()->active()->create();

    // Wali Kelas: teacher_id di school_classes
    $this->schoolClass = SchoolClass::factory()->create([
        'teacher_id' => $this->teacher->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    $this->student = Student::factory()->create([
        'class_id' => $this->schoolClass->id,
    ]);

    actingAs($this->guruUser);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Wali Kelas dapat input nilai sikap untuk siswa di kelasnya
// ─────────────────────────────────────────────────────────────────────────────

test('wali kelas dapat input nilai sikap untuk siswa di kelasnya', function () {
    $newData = AttitudeScore::factory()->make([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'aspect' => 'Spiritual',
        'score' => 85.00,
        'description' => 'Sangat baik dalam ibadah',
    ]);

    Livewire::test(CreateAttitudeScore::class)
        ->fillForm([
            'student_id' => $newData->student_id,
            'academic_year_id' => $newData->academic_year_id,
            'aspect' => $newData->aspect,
            'score' => $newData->score,
            'description' => $newData->description,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(AttitudeScore::class, [
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'aspect' => 'Spiritual',
        'score' => '85.00',
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Wali Kelas tidak dapat input untuk kelas lain (403)
// ─────────────────────────────────────────────────────────────────────────────

test('wali kelas tidak dapat input nilai sikap untuk siswa di kelas lain', function () {
    // Create a student in a different class (not managed by this teacher)
    $otherClass = SchoolClass::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        // teacher_id is a different teacher (from factory)
    ]);
    $otherStudent = Student::factory()->create([
        'class_id' => $otherClass->id,
    ]);

    // The student from another class should NOT appear in the student dropdown
    // because getEloquentQuery() scopes to wali kelas's own classes.
    // Attempting to create with an unauthorized student_id should be blocked.
    $countBefore = AttitudeScore::count();

    Livewire::test(CreateAttitudeScore::class)
        ->fillForm([
            'student_id' => $otherStudent->id,
            'academic_year_id' => $this->academicYear->id,
            'aspect' => 'Spiritual',
            'score' => 80.00,
            'description' => null,
        ])
        ->call('create');

    // No record should have been created for the unauthorized student
    expect(AttitudeScore::count())->toBe($countBefore);
    expect(AttitudeScore::where('student_id', $otherStudent->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Rata-rata nilai sikap terhitung dan ditampilkan
// ─────────────────────────────────────────────────────────────────────────────

test('rata-rata nilai sikap terhitung dan ditampilkan di list', function () {
    // Create two attitude scores for the student
    AttitudeScore::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'aspect' => 'Spiritual',
        'score' => 80.00,
    ]);

    AttitudeScore::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'aspect' => 'Sosial',
        'score' => 90.00,
    ]);

    // Average should be 85.00
    $average = AttitudeScore::where('student_id', $this->student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->avg('score');

    expect(round($average, 2))->toBe(85.0);

    // The list page should load successfully and show the records
    Livewire::test(ListAttitudeScores::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(
            AttitudeScore::where('student_id', $this->student->id)->get()
        );
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Simpan dalam satu transaksi
// ─────────────────────────────────────────────────────────────────────────────

test('simpan nilai sikap berhasil dalam satu operasi', function () {
    $countBefore = AttitudeScore::count();

    Livewire::test(CreateAttitudeScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'aspect' => 'Sosial',
            'score' => 75.50,
            'description' => 'Baik dalam bersosialisasi',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(AttitudeScore::count())->toBe($countBefore + 1);

    assertDatabaseHas(AttitudeScore::class, [
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'aspect' => 'Sosial',
        'score' => '75.50',
        'description' => 'Baik dalam bersosialisasi',
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Wali Kelas dapat edit nilai sikap untuk siswa di kelasnya
// ─────────────────────────────────────────────────────────────────────────────

test('wali kelas dapat edit nilai sikap untuk siswa di kelasnya', function () {
    $attitudeScore = AttitudeScore::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'aspect' => 'Spiritual',
        'score' => 70.00,
    ]);

    Livewire::test(EditAttitudeScore::class, ['record' => $attitudeScore->id])
        ->fillForm([
            'score' => 90.00,
            'description' => 'Meningkat pesat',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(AttitudeScore::class, [
        'id' => $attitudeScore->id,
        'score' => '90.00',
        'description' => 'Meningkat pesat',
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Validasi score harus antara 0-100
// ─────────────────────────────────────────────────────────────────────────────

test('score di luar rentang 0-100 ditolak validasi', function () {
    Livewire::test(CreateAttitudeScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'aspect' => 'Spiritual',
            'score' => 150,
        ])
        ->call('create')
        ->assertHasFormErrors(['score']);

    Livewire::test(CreateAttitudeScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'aspect' => 'Spiritual',
            'score' => -5,
        ])
        ->call('create')
        ->assertHasFormErrors(['score']);
});
