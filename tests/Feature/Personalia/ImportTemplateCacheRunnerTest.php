<?php

use App\Models\Level;
use App\Support\Import\ImportTemplateCacheRunner;
use App\Support\Import\ImportTemplateExporter;

beforeEach(function () {
    ImportTemplateCacheRunner::releaseLock();
});

afterEach(function () {
    ImportTemplateCacheRunner::releaseLock();
});

test('import template cache runner manages lock lifecycle', function () {
    expect(ImportTemplateCacheRunner::isRunning())->toBeFalse()
        ->and(ImportTemplateCacheRunner::acquireLock())->toBeTrue()
        ->and(ImportTemplateCacheRunner::isRunning())->toBeTrue()
        ->and(ImportTemplateCacheRunner::acquireLock())->toBeFalse();

    ImportTemplateCacheRunner::releaseLock();

    expect(ImportTemplateCacheRunner::isRunning())->toBeFalse();
});

test('deploy cache import templates status endpoint reports progress', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    Level::factory()->create(['name' => 'SD']);

    $exporter = app(ImportTemplateExporter::class);
    $exporter->warm('teacher', null, includeFullRegions: false);

    $this->get('/deploy/deploy-token/cache-import-templates/status')
        ->assertSuccessful()
        ->assertJson([
            'running' => false,
            'completed' => false,
            'cached' => [
                'teacher' => true,
                'student_sd' => false,
            ],
        ]);
});

test('status releases orphan lock when all templates are already cached', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    $exporter = app(ImportTemplateExporter::class);

    foreach ($exporter->cacheTargets() as $target) {
        $exporter->warm($target['type'], $target['level_id'], includeFullRegions: false);
    }

    ImportTemplateCacheRunner::acquireLock();

    $lockPath = ImportTemplateCacheRunner::lockPath();
    file_put_contents($lockPath, json_encode([
        'started_at' => now()->subMinutes(2)->toIso8601String(),
        'pid' => getmypid(),
    ], JSON_THROW_ON_ERROR));

    $this->get('/deploy/deploy-token/cache-import-templates/status')
        ->assertSuccessful()
        ->assertJson([
            'running' => false,
            'completed' => true,
        ]);

    expect(ImportTemplateCacheRunner::isRunning())->toBeFalse();
});
