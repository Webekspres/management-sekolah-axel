<?php

/**
 * Preservation Property Tests — Perilaku yang Tidak Boleh Berubah Setelah Fix
 *
 * Test 2.1–2.6 DIHARAPKAN LULUS pada kode yang BELUM diperbaiki.
 * Setiap test mengonfirmasi perilaku baseline yang harus tetap berjalan setelah fix.
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 */

use App\Filament\Guru\Resources\LessonPlans\Pages\EditLessonPlan;
use App\Models\LessonPlan;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\LessonPlanPolicy;
use Filament\Facades\Filament;
use Livewire\Livewire;

// ─────────────────────────────────────────────────────────────────────────────
// Setup: authenticate as guru with teacher profile
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->guruUser = User::factory()->asGuru()->create();
    $this->teacher = Teacher::factory()->create(['user_id' => $this->guruUser->id]);

    $this->actingAs($this->guruUser);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2.1 — Form DRAFT tetap aktif (field file_path tidak disabled)
// **Validates: Requirements 3.1**
// ─────────────────────────────────────────────────────────────────────────────

test('2.1 form DRAFT tetap aktif — field file_path tidak disabled', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'DRAFT',
    ]);

    // Preservation: form RPP berstatus DRAFT harus tetap aktif (tidak dikunci)
    Livewire::test(EditLessonPlan::class, ['record' => $lessonPlan->getRouteKey()])
        ->assertFormFieldEnabled('file_path');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2.2 — Form APPROVED tetap terkunci (field file_path disabled)
// **Validates: Requirements 3.2**
// ─────────────────────────────────────────────────────────────────────────────

test('2.2 form APPROVED tetap terkunci — field file_path disabled', function () {
    $lessonPlan = LessonPlan::factory()->approved()->create([
        'teacher_id' => $this->teacher->id,
    ]);

    // Preservation: form RPP berstatus APPROVED harus tetap terkunci
    Livewire::test(EditLessonPlan::class, ['record' => $lessonPlan->getRouteKey()])
        ->assertFormFieldDisabled('file_path');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2.3 — Save RPP berstatus DRAFT tetap berhasil (tidak ada ValidationException)
// **Validates: Requirements 3.1**
// ─────────────────────────────────────────────────────────────────────────────

test('2.3 save RPP berstatus DRAFT tetap berhasil tanpa error', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'DRAFT',
    ]);

    // Preservation: save RPP berstatus DRAFT tidak boleh menghasilkan error
    Livewire::test(EditLessonPlan::class, ['record' => $lessonPlan->getRouteKey()])
        ->fillForm([
            'topic' => 'Topik yang diperbarui',
        ])
        ->call('save')
        ->assertHasNoErrors();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2.4 — Save RPP berstatus APPROVED tetap ditolak (policy menolak update)
// **Validates: Requirements 3.2**
// ─────────────────────────────────────────────────────────────────────────────

test('2.4 save RPP berstatus APPROVED tetap ditolak — policy menolak update', function () {
    $lessonPlan = LessonPlan::factory()->approved()->create([
        'teacher_id' => $this->teacher->id,
    ]);

    // Preservation: policy update() harus tetap menolak RPP berstatus APPROVED
    $policy = new LessonPlanPolicy;

    expect($policy->update($this->guruUser, $lessonPlan))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2.5 — Alur submitForApproval (DRAFT → PENDING) tetap berjalan
// **Validates: Requirements 3.3**
// ─────────────────────────────────────────────────────────────────────────────

test('2.5 submitForApproval dari DRAFT mengubah status menjadi PENDING', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'DRAFT',
    ]);

    // Preservation: submitForApproval dari DRAFT harus tetap mengubah status ke PENDING
    $lessonPlan->submitForApproval($this->guruUser);

    expect($lessonPlan->fresh()->status)->toBe('PENDING');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2.6 — Alur approve dan markAsRevised di sisi kepsek tetap berjalan
// **Validates: Requirements 3.4, 3.5**
// ─────────────────────────────────────────────────────────────────────────────

test('2.6a approve() pada RPP berstatus PENDING mengubah status menjadi APPROVED', function () {
    $kepsekUser = User::factory()->asKepalaSekolah()->create();

    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'PENDING',
    ]);

    // Preservation: approve() dari PENDING harus tetap mengubah status ke APPROVED
    $lessonPlan->approve($kepsekUser);

    expect($lessonPlan->fresh()->status)->toBe('APPROVED');
});

test('2.6b markAsRevised() pada RPP berstatus PENDING mengubah status menjadi REVISED', function () {
    $kepsekUser = User::factory()->asKepalaSekolah()->create();

    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'PENDING',
    ]);

    // Preservation: markAsRevised() dari PENDING harus tetap mengubah status ke REVISED
    $lessonPlan->markAsRevised($kepsekUser, 'Perlu perbaikan pada bagian tujuan pembelajaran.');

    expect($lessonPlan->fresh()->status)->toBe('REVISED');
});
