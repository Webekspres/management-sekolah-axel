<?php

use App\Filament\Student\Pages\MyGradesPage;
use App\Filament\Student\Pages\MyRaporPage;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Rapor;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('student'));

    $this->siswaUser = User::factory()->asSiswa()->create();
    $this->student = Student::factory()->create(['user_id' => $this->siswaUser->id]);
    $this->academicYear = AcademicYear::factory()->active()->create();
    $this->subject = Subject::factory()->create();

    actingAs($this->siswaUser);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Siswa hanya melihat nilai miliknya sendiri
// ─────────────────────────────────────────────────────────────────────────────

test('siswa hanya melihat nilai miliknya sendiri', function () {
    Grade::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'PH1',
        'score' => 85.00,
    ]);

    // Another student's grade
    $otherStudent = Student::factory()->create();
    Grade::factory()->create([
        'student_id' => $otherStudent->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'PH1',
        'score' => 90.00,
    ]);

    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertSee($this->subject->name)
        ->assertSee('85');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Siswa tidak dapat memodifikasi nilai
// ─────────────────────────────────────────────────────────────────────────────

test('siswa tidak dapat memodifikasi nilai', function () {
    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertDontSee('Simpan')
        ->assertDontSee('Edit');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Siswa dapat melihat nilai dikelompokkan per mapel
// ─────────────────────────────────────────────────────────────────────────────

test('siswa dapat melihat nilai dikelompokkan per mapel dengan semua grade type', function () {
    foreach (['PH1', 'ATS', 'SAS', 'RAPOR'] as $type) {
        Grade::factory()->create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_type' => $type,
            'score' => 80.00,
        ]);
    }

    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertSee($this->subject->name)
        ->assertSee('PH1')
        ->assertSee('ATS')
        ->assertSee('SAS')
        ->assertSee('RAPOR');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Siswa tanpa profil Student mendapat pesan informatif
// ─────────────────────────────────────────────────────────────────────────────

test('siswa tanpa profil student mendapat pesan informatif', function () {
    $userWithoutStudent = User::factory()->asSiswa()->create();
    actingAs($userWithoutStudent);

    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertSee('tidak ditemukan');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Siswa dapat download rapor APPROVED miliknya
// ─────────────────────────────────────────────────────────────────────────────

test('siswa dapat melihat tombol download untuk rapor APPROVED', function () {
    Rapor::factory()->approved()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertSee('Download');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Siswa tidak dapat download rapor DRAFT atau FINALIZED
// ─────────────────────────────────────────────────────────────────────────────

test('siswa tidak dapat download rapor DRAFT atau FINALIZED', function () {
    Rapor::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'status' => 'DRAFT',
    ]);

    Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertSee('Draft')
        ->assertSee('Terfinalisasi');
});
