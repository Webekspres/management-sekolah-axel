<?php

use App\Filament\Clusters\Academic\Resources\SubjectKkms\Pages\CreateSubjectKkm;
use App\Filament\Clusters\Academic\Resources\SubjectKkms\Pages\EditSubjectKkm;
use App\Filament\Clusters\Academic\Resources\SubjectKkms\Pages\ListSubjectKkms;
use App\Models\Level;
use App\Models\Subject;
use App\Models\SubjectKkm;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->asAdmin()->create();
    $this->subject = Subject::factory()->create();
    $this->level = Level::factory()->create();

    actingAs($this->admin);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Admin dapat set KKM per mapel per level
// ─────────────────────────────────────────────────────────────────────────────

test('admin dapat set kkm per mapel per level', function () {
    Livewire::test(CreateSubjectKkm::class)
        ->fillForm([
            'subject_id' => $this->subject->id,
            'level_id' => $this->level->id,
            'kkm' => 75.00,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(SubjectKkm::class, [
        'subject_id' => $this->subject->id,
        'level_id' => $this->level->id,
        'kkm' => '75.00',
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Default KKM 70 digunakan jika tidak dikonfigurasi
// ─────────────────────────────────────────────────────────────────────────────

test('default kkm 70 digunakan jika tidak dikonfigurasi', function () {
    $otherSubject = Subject::factory()->create();
    $otherLevel = Level::factory()->create();

    // No SubjectKkm record for this combination
    $kkm = SubjectKkm::getKkm($otherSubject->id, $otherLevel->id);

    expect($kkm)->toBe(70.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Non-Admin tidak bisa akses resource ini
// ─────────────────────────────────────────────────────────────────────────────

test('non-admin tidak bisa akses subjectkkm resource', function () {
    $guru = User::factory()->asGuru()->create();
    actingAs($guru);

    Livewire::test(ListSubjectKkms::class)
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Admin dapat edit KKM
// ─────────────────────────────────────────────────────────────────────────────

test('admin dapat edit kkm yang sudah ada', function () {
    $kkm = SubjectKkm::factory()->create([
        'subject_id' => $this->subject->id,
        'level_id' => $this->level->id,
        'kkm' => 70.00,
    ]);

    Livewire::test(EditSubjectKkm::class, ['record' => $kkm->id])
        ->fillForm(['kkm' => 80.00])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(SubjectKkm::class, [
        'id' => $kkm->id,
        'kkm' => '80.00',
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Admin dapat melihat list KKM
// ─────────────────────────────────────────────────────────────────────────────

test('admin dapat melihat list kkm', function () {
    $kkm = SubjectKkm::factory()->create([
        'subject_id' => $this->subject->id,
        'level_id' => $this->level->id,
        'kkm' => 75.00,
    ]);

    Livewire::test(ListSubjectKkms::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$kkm]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Validasi KKM harus antara 0-100
// ─────────────────────────────────────────────────────────────────────────────

test('kkm di luar rentang 0-100 ditolak validasi', function () {
    Livewire::test(CreateSubjectKkm::class)
        ->fillForm([
            'subject_id' => $this->subject->id,
            'level_id' => $this->level->id,
            'kkm' => 150,
        ])
        ->call('create')
        ->assertHasFormErrors(['kkm']);
});
