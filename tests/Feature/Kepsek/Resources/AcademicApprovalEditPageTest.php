<?php

use App\Filament\Kepsek\Resources\Kbms\Pages\EditKbm;
use App\Filament\Kepsek\Resources\LessonPlans\LessonPlanResource;
use App\Filament\Kepsek\Resources\LessonPlans\Pages\EditLessonPlan;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->asKepalaSekolah()->create());
    Filament::setCurrentPanel(Filament::getPanel('kepsek'));
});

test('approval rpp kepsek tidak memakai path cluster academic', function () {
    expect(Route::has('filament.kepsek.academic'))->toBeFalse()
        ->and(Route::has('filament.kepsek.resources.lesson-plans.edit'))->toBeTrue();

    $lessonPlan = LessonPlan::factory()->create(['status' => 'PENDING']);
    $url = LessonPlanResource::getUrl('edit', ['record' => $lessonPlan], panel: 'kepsek');
    expect($url)->not->toContain('/academic/');
});

test('kepsek dapat ubah status rpp menjadi revised lewat edit resource', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'status' => 'PENDING',
        'revision_note' => null,
    ]);

    Livewire::test(EditLessonPlan::class, ['record' => $lessonPlan->getRouteKey()])
        ->fillForm([
            'status' => 'REVISED',
            'revision_note' => '<p>Lengkapi indikator pembelajaran.</p>',
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect($lessonPlan->refresh()->status)->toBe('REVISED')
        ->and($lessonPlan->revision_note)->toContain('Lengkapi indikator pembelajaran');
});

test('kepsek dapat ubah status rpp dari revised menjadi approved lewat edit resource', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'status' => 'REVISED',
        'revision_note' => '<p>Perlu revisi lama.</p>',
    ]);

    Livewire::test(EditLessonPlan::class, ['record' => $lessonPlan->getRouteKey()])
        ->fillForm([
            'status' => 'APPROVED',
            'revision_note' => null,
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect($lessonPlan->refresh()->status)->toBe('APPROVED')
        ->and($lessonPlan->revision_note)->toBeNull();
});

test('kepsek dapat ubah status kbm menjadi approved lewat edit resource', function () {
    $kbm = Kbm::factory()->create([
        'status' => 'PENDING',
        'revision_note' => '<p>Catatan lama</p>',
    ]);

    Livewire::test(EditKbm::class, ['record' => $kbm->getRouteKey()])
        ->fillForm([
            'status' => 'APPROVED',
            'revision_note' => null,
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect($kbm->refresh()->status)->toBe('APPROVED')
        ->and($kbm->revision_note)->toBeNull();
});

test('kepsek dapat ubah status kbm dari revised menjadi approved lewat edit resource', function () {
    $kbm = Kbm::factory()->create([
        'status' => 'REVISED',
        'revision_note' => '<p>Perlu revisi lama.</p>',
    ]);

    Livewire::test(EditKbm::class, ['record' => $kbm->getRouteKey()])
        ->fillForm([
            'status' => 'APPROVED',
            'revision_note' => null,
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect($kbm->refresh()->status)->toBe('APPROVED')
        ->and($kbm->revision_note)->toBeNull();
});

test('kepsek melihat ringkasan jumlah kehadiran pada form approval kbm', function () {
    $kbm = Kbm::factory()->create([
        'status' => 'PENDING',
    ]);

    $classId = $kbm->schedule->class_id;
    $students = Student::factory(4)->create(['class_id' => $classId]);

    Attendance::factory()->create([
        'kbm_id' => $kbm->id,
        'student_id' => $students[0]->id,
        'status' => 'HADIR',
    ]);
    Attendance::factory()->create([
        'kbm_id' => $kbm->id,
        'student_id' => $students[1]->id,
        'status' => 'SAKIT',
    ]);
    Attendance::factory()->create([
        'kbm_id' => $kbm->id,
        'student_id' => $students[2]->id,
        'status' => 'IZIN',
    ]);
    Attendance::factory()->create([
        'kbm_id' => $kbm->id,
        'student_id' => $students[3]->id,
        'status' => 'ALPA',
    ]);

    Livewire::test(EditKbm::class, ['record' => $kbm->getRouteKey()])
        ->assertSee('Ringkasan Kehadiran')
        ->assertSee('Total siswa')
        ->assertSee('4')
        ->assertSee('Hadir')
        ->assertSee('Sakit')
        ->assertSee('Izin')
        ->assertSee('Alpa');
});
