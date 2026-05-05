<?php

use App\Filament\Clusters\Academic\Resources\Grades\Pages\CreateGrade;
use App\Filament\Clusters\Academic\Resources\Grades\Pages\EditGrade;
use App\Filament\Clusters\Academic\Resources\Grades\Pages\ListGrades;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->asAdmin()->create();
    $this->academicYear = AcademicYear::factory()->active()->create();
    $this->student = Student::factory()->create();
    $this->subject = Subject::factory()->create();

    actingAs($this->admin);
});

test('admin dapat melihat semua grade', function () {
    $grades = Grade::factory()->count(3)->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'PH1',
    ]);

    Livewire::test(ListGrades::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($grades);
});

test('admin dapat membuat grade baru', function () {
    Livewire::test(CreateGrade::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_type' => 'PH1',
            'score' => 85.00,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Grade::class, [
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'PH1',
        'score' => '85.00',
    ]);
});

test('admin dapat edit grade', function () {
    $grade = Grade::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'PH1',
        'score' => 70.00,
    ]);

    Livewire::test(EditGrade::class, ['record' => $grade->id])
        ->fillForm(['score' => 90.00])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Grade::class, [
        'id' => $grade->id,
        'score' => '90.00',
    ]);
});

test('admin dapat filter grade berdasarkan tahun akademik', function () {
    $grade = Grade::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'ATS',
        'score' => 75.00,
    ]);

    Livewire::test(ListGrades::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$grade]);
});

test('validasi score di luar 0-100 ditolak', function () {
    Livewire::test(CreateGrade::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_type' => 'PH1',
            'score' => 150,
        ])
        ->call('create')
        ->assertHasFormErrors(['score']);
});
