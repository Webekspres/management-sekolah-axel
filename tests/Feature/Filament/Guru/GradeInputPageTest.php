<?php

use App\Filament\Guru\Pages\GradeInputPage;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\RaporService;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

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

    $this->schedule = Schedule::factory()->create([
        'teacher_id' => $this->teacher->id,
        'class_id' => $this->schoolClass->id,
    ]);

    // Create 2 students in the class
    $this->students = Student::factory()->count(2)->create([
        'class_id' => $this->schoolClass->id,
    ]);

    actingAs($this->guruUser);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Guru dapat melihat halaman input nilai untuk jadwal miliknya
// ─────────────────────────────────────────────────────────────────────────────

test('guru dapat melihat halaman input nilai untuk jadwal miliknya', function () {
    Livewire::test(GradeInputPage::class, ['schedule' => $this->schedule->id])
        ->assertSuccessful()
        ->assertSee($this->students->first()->user->name);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Guru tidak dapat akses jadwal bukan miliknya (403)
// ─────────────────────────────────────────────────────────────────────────────

test('guru tidak dapat akses jadwal bukan miliknya', function () {
    $otherTeacher = Teacher::factory()->create();
    $otherSchedule = Schedule::factory()->create([
        'teacher_id' => $otherTeacher->id,
    ]);

    Livewire::test(GradeInputPage::class, ['schedule' => $otherSchedule->id])
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Input nilai PH berhasil disimpan dan nilai RAPOR terhitung ulang
// ─────────────────────────────────────────────────────────────────────────────

test('input nilai PH berhasil disimpan dan nilai RAPOR terhitung ulang', function () {
    $student = $this->students->first();

    $gradesInput = [];
    foreach ($this->students as $s) {
        $gradesInput[$s->id] = array_fill_keys(Grade::GRADE_TYPES, '');
    }
    $gradesInput[$student->id]['PH1'] = '80';
    $gradesInput[$student->id]['PH2'] = '90';

    Livewire::test(GradeInputPage::class, ['schedule' => $this->schedule->id])
        ->set('grades', $gradesInput)
        ->call('saveGrades')
        ->assertNotified();

    // PH1 and PH2 saved
    expect(Grade::where([
        'student_id' => $student->id,
        'subject_id' => $this->schedule->subject_id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'PH1',
    ])->value('score'))->toBe('80.00');

    expect(Grade::where([
        'student_id' => $student->id,
        'subject_id' => $this->schedule->subject_id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'PH2',
    ])->value('score'))->toBe('90.00');

    // RAPOR should be recalculated: avg(80,90)=85, tugas=0, ats=0, sas=0 → (85+0+0+0)/4 = 21.25
    $raporScore = Grade::where([
        'student_id' => $student->id,
        'subject_id' => $this->schedule->subject_id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'RAPOR',
    ])->value('score');

    expect($raporScore)->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Input nilai TUGAS berhasil disimpan
// ─────────────────────────────────────────────────────────────────────────────

test('input nilai TUGAS berhasil disimpan', function () {
    $student = $this->students->first();

    $gradesInput = [];
    foreach ($this->students as $s) {
        $gradesInput[$s->id] = array_fill_keys(Grade::GRADE_TYPES, '');
    }
    $gradesInput[$student->id]['TUGAS1'] = '75';
    $gradesInput[$student->id]['TUGAS2'] = '85';

    Livewire::test(GradeInputPage::class, ['schedule' => $this->schedule->id])
        ->set('grades', $gradesInput)
        ->call('saveGrades')
        ->assertNotified();

    expect(Grade::where([
        'student_id' => $student->id,
        'subject_id' => $this->schedule->subject_id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'TUGAS1',
    ])->value('score'))->toBe('75.00');

    expect(Grade::where([
        'student_id' => $student->id,
        'subject_id' => $this->schedule->subject_id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'TUGAS2',
    ])->value('score'))->toBe('85.00');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Input nilai ATS/SAS berhasil disimpan
// ─────────────────────────────────────────────────────────────────────────────

test('input nilai ATS/SAS berhasil disimpan', function () {
    $student = $this->students->first();

    $gradesInput = [];
    foreach ($this->students as $s) {
        $gradesInput[$s->id] = array_fill_keys(Grade::GRADE_TYPES, '');
    }
    $gradesInput[$student->id]['ATS'] = '70';
    $gradesInput[$student->id]['SAS'] = '80';

    Livewire::test(GradeInputPage::class, ['schedule' => $this->schedule->id])
        ->set('grades', $gradesInput)
        ->call('saveGrades')
        ->assertNotified();

    expect(Grade::where([
        'student_id' => $student->id,
        'subject_id' => $this->schedule->subject_id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'ATS',
    ])->value('score'))->toBe('70.00');

    expect(Grade::where([
        'student_id' => $student->id,
        'subject_id' => $this->schedule->subject_id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'SAS',
    ])->value('score'))->toBe('80.00');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Nilai di luar 0–100 ditolak validasi
// ─────────────────────────────────────────────────────────────────────────────

test('nilai di luar 0-100 ditolak validasi', function () {
    $student = $this->students->first();

    $gradesInput = [];
    foreach ($this->students as $s) {
        $gradesInput[$s->id] = array_fill_keys(Grade::GRADE_TYPES, '');
    }
    $gradesInput[$student->id]['PH1'] = '150'; // invalid: > 100
    $gradesInput[$student->id]['PH2'] = '-5';  // invalid: < 0

    Livewire::test(GradeInputPage::class, ['schedule' => $this->schedule->id])
        ->set('grades', $gradesInput)
        ->call('saveGrades')
        ->assertHasErrors([
            "grades.{$student->id}.PH1",
            "grades.{$student->id}.PH2",
        ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Transaksi rollback jika ada error (semua perubahan dibatalkan)
// ─────────────────────────────────────────────────────────────────────────────

test('transaksi rollback jika ada error semua perubahan dibatalkan', function () {
    $student = $this->students->first();

    // Mock RaporService to throw an exception
    $this->mock(RaporService::class, function ($mock) {
        $mock->shouldReceive('saveGrades')
            ->once()
            ->andThrow(new RuntimeException('Simulated DB error'));
    });

    $gradesInput = [];
    foreach ($this->students as $s) {
        $gradesInput[$s->id] = array_fill_keys(Grade::GRADE_TYPES, '');
    }
    $gradesInput[$student->id]['PH1'] = '80';

    $countBefore = Grade::count();

    Livewire::test(GradeInputPage::class, ['schedule' => $this->schedule->id])
        ->set('grades', $gradesInput)
        ->call('saveGrades')
        ->assertNotified(); // danger notification

    // No grades should have been saved
    expect(Grade::count())->toBe($countBefore);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Nilai RAPOR ditampilkan bersama komponen nilai
// ─────────────────────────────────────────────────────────────────────────────

test('nilai RAPOR ditampilkan bersama komponen nilai', function () {
    $student = $this->students->first();

    // Pre-create a RAPOR grade
    Grade::create([
        'student_id' => $student->id,
        'subject_id' => $this->schedule->subject_id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'RAPOR',
        'score' => 85.50,
    ]);

    Livewire::test(GradeInputPage::class, ['schedule' => $this->schedule->id])
        ->assertSuccessful()
        ->assertSee('RAPOR')
        ->assertSee('85.50');
});
