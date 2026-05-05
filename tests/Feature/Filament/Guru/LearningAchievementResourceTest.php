<?php

use App\Filament\Guru\Resources\LearningAchievements\Pages\CreateLearningAchievement;
use App\Filament\Guru\Resources\LearningAchievements\Pages\EditLearningAchievement;
use App\Filament\Guru\Resources\LearningAchievements\Pages\ListLearningAchievements;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\LearningAchievement;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
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
    $this->schoolClass = SchoolClass::factory()->create(['academic_year_id' => $this->academicYear->id]);
    $this->subject = Subject::factory()->create();
    $this->schedule = Schedule::factory()->create([
        'teacher_id' => $this->teacher->id,
        'class_id' => $this->schoolClass->id,
        'subject_id' => $this->subject->id,
    ]);
    $this->student = Student::factory()->create(['class_id' => $this->schoolClass->id]);

    actingAs($this->guruUser);
});

test('guru dapat input capaian pembelajaran untuk jadwal miliknya', function () {
    Livewire::test(CreateLearningAchievement::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'topic_coverage' => 'Bab 1: Pengenalan Aljabar',
            'notes' => 'Siswa memahami konsep dasar dengan baik',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(LearningAchievement::class, [
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'topic_coverage' => 'Bab 1: Pengenalan Aljabar',
    ]);
});

test('guru tidak bisa input capaian pembelajaran untuk mapel bukan jadwalnya', function () {
    $otherSubject = Subject::factory()->create();
    $countBefore = LearningAchievement::count();

    Livewire::test(CreateLearningAchievement::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'subject_id' => $otherSubject->id,
            'academic_year_id' => $this->academicYear->id,
            'topic_coverage' => 'Unauthorized topic',
        ])
        ->call('create');

    expect(LearningAchievement::count())->toBe($countBefore);
    expect(LearningAchievement::where('subject_id', $otherSubject->id)->exists())->toBeFalse();
});

test('rata-rata PH ATS SAS ditampilkan sebagai referensi di list', function () {
    // Create some grades for reference
    Grade::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'PH1',
        'score' => 80.00,
    ]);
    Grade::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'ATS',
        'score' => 75.00,
    ]);

    LearningAchievement::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    Livewire::test(ListLearningAchievements::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(
            LearningAchievement::where('student_id', $this->student->id)->get()
        );
});

test('guru dapat edit capaian pembelajaran untuk jadwal miliknya', function () {
    $record = LearningAchievement::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'topic_coverage' => 'Bab 1',
        'notes' => 'Catatan awal',
    ]);

    Livewire::test(EditLearningAchievement::class, ['record' => $record->id])
        ->fillForm([
            'topic_coverage' => 'Bab 1 & 2: Aljabar dan Geometri',
            'notes' => 'Catatan diperbarui',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(LearningAchievement::class, [
        'id' => $record->id,
        'topic_coverage' => 'Bab 1 & 2: Aljabar dan Geometri',
        'notes' => 'Catatan diperbarui',
    ]);
});
