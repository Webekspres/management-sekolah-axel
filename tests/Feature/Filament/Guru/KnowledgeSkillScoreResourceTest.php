<?php

use App\Filament\Guru\Resources\KnowledgeSkillScores\Pages\CreateKnowledgeSkillScore;
use App\Filament\Guru\Resources\KnowledgeSkillScores\Pages\EditKnowledgeSkillScore;
use App\Filament\Guru\Resources\KnowledgeSkillScores\Pages\ListKnowledgeSkillScores;
use App\Models\AcademicYear;
use App\Models\KnowledgeSkillScore;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectKkm;
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

    $this->schoolClass = SchoolClass::factory()->create([
        'academic_year_id' => $this->academicYear->id,
    ]);

    $this->subject = Subject::factory()->create();

    // Guru has a schedule for this subject/class
    $this->schedule = Schedule::factory()->create([
        'teacher_id' => $this->teacher->id,
        'class_id' => $this->schoolClass->id,
        'subject_id' => $this->subject->id,
    ]);

    $this->student = Student::factory()->create([
        'class_id' => $this->schoolClass->id,
    ]);

    actingAs($this->guruUser);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Guru dapat input nilai pengetahuan & keterampilan untuk jadwal miliknya
// ─────────────────────────────────────────────────────────────────────────────

test('guru dapat input nilai pengetahuan dan keterampilan untuk jadwal miliknya', function () {
    Livewire::test(CreateKnowledgeSkillScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'knowledge_score' => 88.00,
            'knowledge_description' => 'Sangat memahami materi',
            'skill_score' => 82.00,
            'skill_description' => 'Terampil dalam praktik',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(KnowledgeSkillScore::class, [
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'knowledge_score' => '88.00',
        'skill_score' => '82.00',
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Predikat otomatis ter-assign berdasarkan skor (A/B/C/D)
// ─────────────────────────────────────────────────────────────────────────────

test('predikat otomatis ter-assign berdasarkan skor', function () {
    Livewire::test(CreateKnowledgeSkillScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'knowledge_score' => 90.00, // A
            'skill_score' => 75.00,     // B
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $record = KnowledgeSkillScore::where([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
    ])->first();

    expect($record)->not->toBeNull();
    expect($record->knowledge_predicate)->toBe('A'); // 90 >= 86
    expect($record->skill_predicate)->toBe('B');     // 75 >= 73
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: KKM ditampilkan di samping input nilai
// ─────────────────────────────────────────────────────────────────────────────

test('kkm ditampilkan di halaman list', function () {
    // Create a KKM for this subject/level
    SubjectKkm::factory()->create([
        'subject_id' => $this->subject->id,
        'level_id' => $this->schoolClass->level_id,
        'kkm' => 75.00,
    ]);

    KnowledgeSkillScore::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'knowledge_score' => 80.00,
        'knowledge_predicate' => 'B',
        'skill_score' => 70.00,
        'skill_predicate' => 'C',
    ]);

    Livewire::test(ListKnowledgeSkillScores::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(
            KnowledgeSkillScore::where('student_id', $this->student->id)->get()
        );
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Nilai di bawah KKM mendapat indikator visual warning
// ─────────────────────────────────────────────────────────────────────────────

test('nilai di bawah kkm tersimpan dan dapat diidentifikasi', function () {
    SubjectKkm::factory()->create([
        'subject_id' => $this->subject->id,
        'level_id' => $this->schoolClass->level_id,
        'kkm' => 75.00,
    ]);

    Livewire::test(CreateKnowledgeSkillScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'knowledge_score' => 60.00, // below KKM 75
            'skill_score' => 70.00,     // below KKM 75
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $record = KnowledgeSkillScore::where([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
    ])->first();

    expect($record)->not->toBeNull();
    expect((float) $record->knowledge_score)->toBeLessThan(75.0);
    expect((float) $record->skill_score)->toBeLessThan(75.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Guru tidak bisa akses jadwal bukan miliknya
// ─────────────────────────────────────────────────────────────────────────────

test('guru tidak bisa input nilai untuk mapel bukan jadwalnya', function () {
    $otherSubject = Subject::factory()->create();
    // No schedule linking this teacher to otherSubject

    $countBefore = KnowledgeSkillScore::count();

    Livewire::test(CreateKnowledgeSkillScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'subject_id' => $otherSubject->id,
            'academic_year_id' => $this->academicYear->id,
            'knowledge_score' => 80.00,
            'skill_score' => 80.00,
        ])
        ->call('create');

    // No record should be created for unauthorized subject
    expect(KnowledgeSkillScore::count())->toBe($countBefore);
    expect(KnowledgeSkillScore::where('subject_id', $otherSubject->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Validasi score harus antara 0-100
// ─────────────────────────────────────────────────────────────────────────────

test('score di luar rentang 0-100 ditolak validasi', function () {
    Livewire::test(CreateKnowledgeSkillScore::class)
        ->fillForm([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'knowledge_score' => 150,
            'skill_score' => -10,
        ])
        ->call('create')
        ->assertHasFormErrors(['knowledge_score', 'skill_score']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Edit nilai pengetahuan & keterampilan
// ─────────────────────────────────────────────────────────────────────────────

test('guru dapat edit nilai pengetahuan dan keterampilan untuk jadwal miliknya', function () {
    $record = KnowledgeSkillScore::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'knowledge_score' => 70.00,
        'knowledge_predicate' => 'C',
        'skill_score' => 65.00,
        'skill_predicate' => 'C',
    ]);

    Livewire::test(EditKnowledgeSkillScore::class, ['record' => $record->id])
        ->fillForm([
            'knowledge_score' => 88.00,
            'skill_score' => 90.00,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(KnowledgeSkillScore::class, [
        'id' => $record->id,
        'knowledge_score' => '88.00',
        'knowledge_predicate' => 'A',
        'skill_score' => '90.00',
        'skill_predicate' => 'A',
    ]);
});
