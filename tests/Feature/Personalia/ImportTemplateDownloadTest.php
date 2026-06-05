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
    $exporter = app(ImportTemplateExporter::class);

    $exporter->warm('student', $level->id);

    $response = $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $level->id])
        ->get(route('personalia.import-template', ['type' => 'student']));

    $response->assertOk();
    $response->assertDownload('template-import-siswa.xlsx');

    expect(filesize($exporter->cachedPath('student', $level->id)))->toBeGreaterThan(1024);
});

test('admin can download pre-cached teacher import template', function () {
    $admin = User::factory()->asAdmin()->create();

    app(ImportTemplateExporter::class)->warm('teacher', null);

    $response = $this->actingAs($admin)
        ->get(route('personalia.import-template', ['type' => 'teacher']));

    $response->assertOk();
    $response->assertDownload('template-import-guru.xlsx');
});

test('download builds template on demand when not cached', function () {
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
        ->assertOk()
        ->assertDownload('template-import-siswa.xlsx');
});

test('download rebuilds template when cached file is empty', function () {
    $admin = User::factory()->asAdmin()->create();
    $level = Level::factory()->create(['name' => 'SMA']);
    $exporter = app(ImportTemplateExporter::class);
    $cachedPath = $exporter->cachedPath('student', $level->id);

    $cacheDir = dirname($cachedPath);
    if (! is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    file_put_contents($cachedPath, '');

    $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $level->id])
        ->get(route('personalia.import-template', ['type' => 'student']))
        ->assertOk()
        ->assertDownload('template-import-siswa.xlsx');

    expect(filesize($cachedPath))->toBeGreaterThan(0);
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
