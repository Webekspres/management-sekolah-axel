<?php

use App\Filament\Clusters\Academic\Resources\Rapors\Pages\ListRapors;
use App\Models\AcademicYear;
use App\Models\Rapor;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->asAdmin()->create();
    $this->academicYear = AcademicYear::factory()->active()->create();
    $this->student = Student::factory()->create();

    actingAs($this->admin);
});

test('admin dapat melihat semua rapor', function () {
    $rapors = Rapor::factory()->count(3)->create([
        'academic_year_id' => $this->academicYear->id,
    ]);

    Livewire::test(ListRapors::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($rapors);
});

test('admin dapat filter rapor berdasarkan status', function () {
    $draft = Rapor::factory()->create(['status' => 'DRAFT', 'academic_year_id' => $this->academicYear->id]);
    $approved = Rapor::factory()->approved()->create(['academic_year_id' => $this->academicYear->id]);

    Livewire::test(ListRapors::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$draft, $approved]);
});

test('admin dapat mengubah status rapor ke APPROVED', function () {
    $rapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    Livewire::test(ListRapors::class)
        ->callAction(TestAction::make('approve')->table($rapor))
        ->assertNotified();

    assertDatabaseHas(Rapor::class, [
        'id' => $rapor->id,
        'status' => 'APPROVED',
    ]);
});

test('admin dapat mengubah status rapor ke DRAFT via reject', function () {
    $rapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    Livewire::test(ListRapors::class)
        ->callAction(TestAction::make('reject')->table($rapor), data: [
            'rejection_note' => 'Perlu koreksi nilai',
        ])
        ->assertNotified();

    assertDatabaseHas(Rapor::class, [
        'id' => $rapor->id,
        'status' => 'DRAFT',
        'rejection_note' => 'Perlu koreksi nilai',
    ]);
});

test('non-admin tidak bisa akses rapor resource di admin panel', function () {
    $guru = User::factory()->asGuru()->create();
    actingAs($guru);

    Livewire::test(ListRapors::class)
        ->assertForbidden();
});
