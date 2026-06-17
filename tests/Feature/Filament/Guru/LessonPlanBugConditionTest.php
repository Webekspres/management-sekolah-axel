<?php

/**
 * Bug Condition Exploration Tests — Konfirmasi Bug Ada di Kode yang Belum Diperbaiki
 *
 * Test ini DIHARAPKAN GAGAL pada kode yang BELUM diperbaiki.
 * Setiap test meng-assert perilaku yang BENAR (expected behavior setelah fix),
 * sehingga akan GAGAL pada kode buggy — yang mengonfirmasi bug ada.
 *
 * Setelah fix diimplementasikan, test-test ini akan LULUS.
 *
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3**
 */

use App\Filament\Guru\Resources\LessonPlans\Pages\CreateLessonPlan;
use App\Filament\Guru\Resources\LessonPlans\Pages\EditLessonPlan;
use App\Models\LessonPlan;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// ─────────────────────────────────────────────────────────────────────────────
// Setup: authenticate as guru with teacher profile
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->guruUser = User::factory()->asGuru()->create();
    $this->teacher = Teacher::factory()->create(['user_id' => $this->guruUser->id]);

    actingAs($this->guruUser);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 1.1 — Field file_path TIDAK disabled ketika status REVISED
// BUG: isContentLocked() mengembalikan true untuk REVISED (seharusnya false)
// Test ini akan GAGAL pada kode buggy karena field masih disabled
// **Validates: Requirements 1.1, 2.1**
// ─────────────────────────────────────────────────────────────────────────────

test('1.1 field file_path tidak disabled ketika status REVISED (expected: enabled)', function () {
    $lessonPlan = LessonPlan::factory()->revised()->create([
        'teacher_id' => $this->teacher->id,
    ]);

    // Expected behavior: form RPP berstatus REVISED harus AKTIF (tidak dikunci)
    // BUG: isContentLocked() salah memasukkan REVISED ke daftar status terkunci
    Livewire::test(EditLessonPlan::class, ['record' => $lessonPlan->getRouteKey()])
        ->assertFormFieldEnabled('file_path');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 1.2 — Save RPP berstatus REVISED berhasil tanpa ValidationException
// BUG: mutateFormDataBeforeSave() melempar exception untuk REVISED (seharusnya tidak)
// Test ini akan GAGAL pada kode buggy karena exception dilempar
// **Validates: Requirements 1.2, 2.2**
// ─────────────────────────────────────────────────────────────────────────────

test('1.2 save RPP berstatus REVISED berhasil tanpa error (expected: no exception)', function () {
    $lessonPlan = LessonPlan::factory()->revised()->create([
        'teacher_id' => $this->teacher->id,
    ]);

    // Expected behavior: save RPP berstatus REVISED tidak boleh melempar exception
    // BUG: mutateFormDataBeforeSave() hanya mengizinkan DRAFT dan PENDING, bukan REVISED
    Livewire::test(EditLessonPlan::class, ['record' => $lessonPlan->getRouteKey()])
        ->fillForm([
            'topic' => 'Topik yang diperbarui setelah revisi',
        ])
        ->call('save')
        ->assertHasNoErrors();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 1.3 — Save RPP berstatus PENDING GAGAL dengan validation error
// BUG: mutateFormDataBeforeSave() mengizinkan PENDING (seharusnya ditolak)
// Test ini akan GAGAL pada kode buggy karena save berhasil tanpa error
// **Validates: Requirements 1.3, 2.4**
// ─────────────────────────────────────────────────────────────────────────────

test('1.3 save RPP berstatus PENDING gagal dengan validation error (expected: rejected)', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'PENDING',
    ]);

    // Expected behavior: save RPP berstatus PENDING harus ditolak (RPP sedang menunggu keputusan kepsek)
    // BUG: mutateFormDataBeforeSave() salah mengizinkan PENDING untuk disimpan
    Livewire::test(EditLessonPlan::class, ['record' => $lessonPlan->getRouteKey()])
        ->fillForm([
            'topic' => 'Topik yang diperbarui saat pending',
        ])
        ->call('save')
        ->assertHasErrors(['status']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 1.4 — Create RPP baru tersimpan ke database
// BUG: RPP baru tidak tersimpan ke database (root cause perlu dikonfirmasi)
// Test ini akan GAGAL jika create broken
// **Validates: Requirements 1.4, 2.3**
// ─────────────────────────────────────────────────────────────────────────────

test('1.4 create RPP baru tersimpan ke database (expected: count increased by 1)', function () {
    Storage::fake('public');

    $countBefore = LessonPlan::count();

    $uploadedFile = UploadedFile::fake()->create('RPP-Baru.pdf', 256, 'application/pdf');

    // Expected behavior: create RPP baru harus menyimpan record ke database
    Livewire::test(CreateLessonPlan::class)
        ->fillForm([
            'subject_id' => Subject::factory()->create()->id,
            'class_id' => SchoolClass::factory()->create()->id,
            'topic' => 'Topik RPP Baru',
            'implementation_date' => now()->addDays(7)->format('Y-m-d'),
            'file_path' => $uploadedFile,
        ])
        ->call('create')
        ->assertHasNoErrors();

    expect(LessonPlan::count())->toBe($countBefore + 1);
});
