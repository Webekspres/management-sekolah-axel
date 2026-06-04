<?php

use App\Models\Level;
use App\Models\User;
use App\Support\Import\ImportTemplateExporter;

test('cache command builds import template files', function () {
    Level::factory()->create(['name' => 'SD']);
    Level::factory()->create(['name' => 'SMP']);

    $this->artisan('personalia:cache-import-templates')
        ->assertSuccessful();

    $exporter = app(ImportTemplateExporter::class);

    expect($exporter->isCached('teacher', null))->toBeTrue()
        ->and($exporter->isCached('student', Level::query()->where('name', 'SD')->value('id')))->toBeTrue();
});

test('admin can download pre-cached student import template', function () {
    $admin = User::factory()->asAdmin()->create();
    $level = Level::factory()->create(['name' => 'SD']);

    app(ImportTemplateExporter::class)->warm('student', $level->id);

    $response = $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $level->id])
        ->get(route('personalia.import-template', ['type' => 'student']));

    $response->assertOk();
    $response->assertDownload('template-import-siswa.xlsx');
});

test('admin can download pre-cached teacher import template', function () {
    $admin = User::factory()->asAdmin()->create();

    app(ImportTemplateExporter::class)->warm('teacher', null);

    $response = $this->actingAs($admin)
        ->get(route('personalia.import-template', ['type' => 'teacher']));

    $response->assertOk();
    $response->assertDownload('template-import-guru.xlsx');
});

test('download returns not found when template is not cached', function () {
    $admin = User::factory()->asAdmin()->create();
    $level = Level::factory()->create(['name' => 'SMA']);

    $cacheDir = storage_path('app/import-templates');
    if (is_dir($cacheDir)) {
        foreach (glob($cacheDir.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $level->id])
        ->get(route('personalia.import-template', ['type' => 'student']))
        ->assertNotFound();
});

test('guest cannot download import template', function () {
    $this->get(route('personalia.import-template', ['type' => 'student']))
        ->assertRedirect('/login');
});

test('non admin cannot download import template', function () {
    $guru = User::factory()->asGuru()->create();

    $this->actingAs($guru)
        ->get(route('personalia.import-template', ['type' => 'student']))
        ->assertForbidden();
});
