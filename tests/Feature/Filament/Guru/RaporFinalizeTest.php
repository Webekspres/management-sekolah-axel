<?php

use App\Filament\Guru\Resources\Rapors\Pages\ListRapors;
use App\Models\AcademicYear;
use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->academicYear = AcademicYear::factory()->active()->create();
    $this->subject = Subject::factory()->create();

    $this->waliKelasUser = User::factory()->asGuru()->create();
    $this->waliKelasTeacher = Teacher::factory()->create(['user_id' => $this->waliKelasUser->id]);

    $this->schoolClass = SchoolClass::factory()->create([
        'teacher_id' => $this->waliKelasTeacher->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    $this->student = Student::factory()->create([
        'class_id' => $this->schoolClass->id,
    ]);

    $this->rapor = Rapor::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'status' => 'DRAFT',
    ]);

    actingAs($this->waliKelasUser);
});

test('wali kelas dapat ajukan rapor dengan program dan sumber pembelajaran', function () {
    createCompleteGrades($this->student, $this->subject, $this->academicYear);

    Livewire::test(ListRapors::class)
        ->callAction(TestAction::make('finalize')->table($this->rapor), [
            'program' => 'Peminatan IPA',
            'sumber_pembelajaran' => 'Buku teks kurikulum merdeka',
        ])
        ->assertNotified();

    assertDatabaseHas(Rapor::class, [
        'id' => $this->rapor->id,
        'status' => 'FINALIZED',
        'program' => 'Peminatan IPA',
        'sumber_pembelajaran' => 'Buku teks kurikulum merdeka',
    ]);
});

test('wali kelas tidak dapat ajukan rapor jika nilai belum lengkap', function () {
    Livewire::test(ListRapors::class)
        ->callAction(TestAction::make('finalize')->table($this->rapor), [
            'program' => 'Reguler',
            'sumber_pembelajaran' => 'Modul sekolah',
        ])
        ->assertNotified();

    assertDatabaseHas(Rapor::class, [
        'id' => $this->rapor->id,
        'status' => 'DRAFT',
    ]);
});
