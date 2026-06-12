<?php

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

    $exporter = app(ImportTemplateExporter::class);
    $exporter->warm('teacher', null, includeFullRegions: false);

    $this->get('/deploy/deploy-token/cache-import-templates/status')
        ->assertSuccessful()
        ->assertJson([
            'running' => false,
            'cached' => [
                'teacher' => true,
            ],
        ]);
});
