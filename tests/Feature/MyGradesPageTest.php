<?php

use App\Filament\Student\Pages\MyGradesPage;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('student'));
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 1: Menampilkan pesan "Profil siswa tidak ditemukan" ketika user tidak
//         punya student profile
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan pesan profil siswa tidak ditemukan ketika user tidak punya student profile', function (): void {
    $user = User::factory()->asSiswa()->create();
    actingAs($user);

    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertSee('Profil siswa tidak ditemukan');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2: Menampilkan empty state "Belum ada nilai" ketika student ada tapi
//         tidak ada nilai
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan empty state belum ada nilai ketika student ada tapi tidak ada nilai', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    AcademicYear::factory()->active()->create();

    actingAs($user);

    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertSee('Belum ada nilai');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3: Menampilkan nama tahun akademik aktif (badge)
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan nama tahun akademik aktif', function (): void {
    $user = User::factory()->asSiswa()->create();
    Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->active()->create(['name' => '2024/2025']);

    actingAs($user);

    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertSee($academicYear->name);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 4: Menampilkan semua mata pelajaran yang punya nilai di tabel
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan semua mata pelajaran yang punya nilai di tabel', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->active()->create();

    $subjectA = Subject::factory()->create(['name' => 'Matematika']);
    $subjectB = Subject::factory()->create(['name' => 'Bahasa Indonesia']);

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subjectA->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'PH1',
        'score' => 80.00,
    ]);

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subjectB->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'PH1',
        'score' => 75.00,
    ]);

    actingAs($user);

    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertSee('Matematika')
        ->assertSee('Bahasa Indonesia');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 5: Nilai RAPOR ditampilkan dengan format dua desimal (e.g. "93.75")
// ─────────────────────────────────────────────────────────────────────────────

test('nilai RAPOR ditampilkan dengan format dua desimal', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->active()->create();
    $subject = Subject::factory()->create();

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'RAPOR',
        'score' => 93.75,
    ]);

    actingAs($user);

    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertSee('93.75');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 6: Nilai yang tidak ada ditampilkan sebagai "—"
// ─────────────────────────────────────────────────────────────────────────────

test('nilai yang tidak ada ditampilkan sebagai placeholder em dash', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->active()->create();
    $subject = Subject::factory()->create();

    // Only create PH1 — all other grade types are missing
    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'PH1',
        'score' => 80.00,
    ]);

    actingAs($user);

    Livewire::test(MyGradesPage::class)
        ->assertSuccessful()
        ->assertSee('—');
});
