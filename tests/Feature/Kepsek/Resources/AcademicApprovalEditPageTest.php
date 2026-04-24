<?php

use App\Filament\Kepsek\Resources\Kbms\Pages\EditKbm;
use App\Filament\Kepsek\Resources\LessonPlans\Pages\EditLessonPlan;
use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->asKepalaSekolah()->create());
    Filament::setCurrentPanel(Filament::getPanel('kepsek'));
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
