<?php

use App\Filament\Student\Pages\MyRaporPage;
use App\Models\AcademicYear;
use App\Models\Rapor;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
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

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertSee('Profil siswa tidak ditemukan');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2: Menampilkan empty state "Belum ada rapor" ketika student ada tapi
//         tidak ada rapor
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan empty state belum ada rapor ketika student ada tapi tidak ada rapor', function (): void {
    $user = User::factory()->asSiswa()->create();
    Student::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertSee('Belum ada rapor');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3: Menampilkan badge status "Disetujui" untuk rapor APPROVED
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan badge status Disetujui untuk rapor APPROVED', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->create();

    Rapor::factory()->approved()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
    ]);

    actingAs($user);

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertSee('Disetujui');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 4: Menampilkan badge status "Terfinalisasi" untuk rapor FINALIZED
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan badge status Terfinalisasi untuk rapor FINALIZED', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->create();

    Rapor::factory()->finalized()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
    ]);

    actingAs($user);

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertSee('Terfinalisasi');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 5: Menampilkan badge status "Draft" untuk rapor DRAFT
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan badge status Draft untuk rapor DRAFT', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->create();

    Rapor::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'status' => 'DRAFT',
    ]);

    actingAs($user);

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertSee('Draft');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 6: Menampilkan tombol Download untuk rapor APPROVED yang punya file_path
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan tombol download untuk rapor APPROVED yang punya file_path', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->create();

    $rapor = Rapor::factory()->approved()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
    ]);

    actingAs($user);

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertActionVisible(TestAction::make('download')->table($rapor));
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 7: Tidak menampilkan tombol Download untuk rapor DRAFT
// ─────────────────────────────────────────────────────────────────────────────

test('tidak menampilkan tombol download untuk rapor DRAFT', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->create();

    $rapor = Rapor::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'status' => 'DRAFT',
        'file_path' => null,
    ]);

    actingAs($user);

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertActionHidden(TestAction::make('download')->table($rapor));
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 8: Menampilkan kolom Tahun Akademik, Semester, Status, Tanggal Disetujui
// ─────────────────────────────────────────────────────────────────────────────

test('menampilkan kolom Tahun Akademik Semester Status dan Tanggal Disetujui', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->create([
        'name' => '2024/2025',
        'semester' => 'Ganjil',
    ]);

    Rapor::factory()->approved()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
    ]);

    actingAs($user);

    Livewire::test(MyRaporPage::class)
        ->assertSuccessful()
        ->assertSee('Tahun Akademik')
        ->assertSee('Semester')
        ->assertSee('Status')
        ->assertSee('Tanggal Disetujui')
        ->assertSee('2024/2025')
        ->assertSee('Ganjil');
});
