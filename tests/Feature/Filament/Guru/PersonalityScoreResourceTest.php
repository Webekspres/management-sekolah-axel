<?php

use App\Filament\Guru\Resources\PersonalityScores\Pages\CreatePersonalityScore;
use App\Filament\Guru\Resources\PersonalityScores\Pages\EditPersonalityScore;
use App\Filament\Guru\Resources\PersonalityScores\Pages\ListPersonalityScores;
use App\Models\AcademicYear;
use App\Models\PersonalityScore;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

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
    $this->student = Student::factory()->create(['class_id' => $this->schoolClass->id]);

    actingAs($this->guruUser);
});

test('wali kelas dapat input kepribadian untuk siswa di kelasnya', function () {
    Livewire::test(CreatePersonalityScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'kedisiplinan' => 'A',
            'kerapihan' => 'B',
            'kerajinan' => 'A',
            'kesopanan' => 'B',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(PersonalityScore::class, [
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'kedisiplinan' => 'A',
        'kerapihan' => 'B',
        'kerajinan' => 'A',
        'kesopanan' => 'B',
    ]);
});

test('nilai selain A B C D ditolak validasi', function () {
    Livewire::test(CreatePersonalityScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'kedisiplinan' => 'E', // invalid
            'kerapihan' => 'B',
            'kerajinan' => 'A',
            'kesopanan' => 'B',
        ])
        ->call('create')
        ->assertHasFormErrors(['kedisiplinan']);
});

test('wali kelas tidak bisa input kepribadian untuk siswa di kelas lain', function () {
    $otherClass = SchoolClass::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        // different teacher_id from factory
    ]);
    $otherStudent = Student::factory()->create(['class_id' => $otherClass->id]);

    $countBefore = PersonalityScore::count();

    Livewire::test(CreatePersonalityScore::class)
        ->fillForm([
            'student_id' => $otherStudent->id,
            'academic_year_id' => $this->academicYear->id,
            'kedisiplinan' => 'A',
            'kerapihan' => 'A',
            'kerajinan' => 'A',
            'kesopanan' => 'A',
        ])
        ->call('create');

    expect(PersonalityScore::count())->toBe($countBefore);
    expect(PersonalityScore::where('student_id', $otherStudent->id)->exists())->toBeFalse();
});

test('wali kelas dapat edit kepribadian siswa di kelasnya', function () {
    $record = PersonalityScore::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'kedisiplinan' => 'C',
        'kerapihan' => 'C',
        'kerajinan' => 'C',
        'kesopanan' => 'C',
    ]);

    Livewire::test(EditPersonalityScore::class, ['record' => $record->id])
        ->fillForm([
            'kedisiplinan' => 'A',
            'kerapihan' => 'B',
            'kerajinan' => 'A',
            'kesopanan' => 'A',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(PersonalityScore::class, [
        'id' => $record->id,
        'kedisiplinan' => 'A',
        'kerapihan' => 'B',
    ]);
});

test('list kepribadian hanya menampilkan siswa di kelas wali kelas', function () {
    $myScore = PersonalityScore::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    // Score for student in another class
    $otherClass = SchoolClass::factory()->create();
    $otherStudent = Student::factory()->create(['class_id' => $otherClass->id]);
    PersonalityScore::factory()->create([
        'student_id' => $otherStudent->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    Livewire::test(ListPersonalityScores::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$myScore])
        ->assertCanNotSeeTableRecords(
            PersonalityScore::where('student_id', $otherStudent->id)->get()
        );
});
