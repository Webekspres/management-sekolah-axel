<?php

use App\Filament\Kepsek\Resources\Rapors\Pages\ListRapors;
use App\Filament\Kepsek\Resources\Rapors\RaporResource;
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
    Filament::setCurrentPanel(Filament::getPanel('kepsek'));

    $this->kepsek = User::factory()->asKepalaSekolah()->create();
    $this->academicYear = AcademicYear::factory()->active()->create();
    $this->student = Student::factory()->create();

    actingAs($this->kepsek);
});

test('kepsek dapat melihat semua rapor dengan status masing-masing', function () {
    $draft = Rapor::factory()->create(['status' => 'DRAFT', 'academic_year_id' => $this->academicYear->id]);
    $finalized = Rapor::factory()->finalized()->create(['academic_year_id' => $this->academicYear->id]);
    $approved = Rapor::factory()->approved()->create(['academic_year_id' => $this->academicYear->id]);

    Livewire::test(ListRapors::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$draft, $finalized, $approved]);
});

test('kepsek tidak dapat edit data nilai (hanya approve/reject)', function () {
    // canEdit returns false on the resource
    expect(RaporResource::canEdit(Rapor::factory()->make()))->toBeFalse();
    expect(RaporResource::canCreate())->toBeFalse();
    expect(RaporResource::canDelete(Rapor::factory()->make()))->toBeFalse();
});

test('kepsek dapat approve rapor FINALIZED', function () {
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

test('kepsek dapat reject rapor FINALIZED dengan catatan', function () {
    $rapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    Livewire::test(ListRapors::class)
        ->callAction(TestAction::make('reject')->table($rapor), data: [
            'rejection_note' => 'Nilai perlu dikoreksi',
        ])
        ->assertNotified();

    assertDatabaseHas(Rapor::class, [
        'id' => $rapor->id,
        'status' => 'DRAFT',
        'rejection_note' => 'Nilai perlu dikoreksi',
    ]);
});

test('non-kepsek tidak bisa akses rapor resource di kepsek panel', function () {
    $guru = User::factory()->asGuru()->create();
    actingAs($guru);

    Livewire::test(ListRapors::class)
        ->assertForbidden();
});
