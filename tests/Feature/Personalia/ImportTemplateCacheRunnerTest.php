<?php

use App\Models\City;
use App\Models\Level;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use App\Support\Import\ImportTemplateCacheRunner;
use App\Support\Import\ImportTemplateExporter;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    ImportTemplateCacheRunner::releaseLock();
});

afterEach(function () {
    ImportTemplateCacheRunner::releaseLock();
});

test('dispatch runs cache command after http response via terminating callback', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('personalia:cache-import-templates', [])
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->andReturn('OK');

    $result = ImportTemplateCacheRunner::dispatchInBackground();

    expect($result['status'])->toBe('started')
        ->and($result['mode'])->toBe('after_response');

    app()->terminate();
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

test('status releases stale lock when no template files were created', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    $exporter = app(ImportTemplateExporter::class);
    $cachedPath = $exporter->cachedPath('teacher', null);

    if (is_file($cachedPath)) {
        unlink($cachedPath);
    }

    ImportTemplateCacheRunner::acquireLock();

    $lockPath = ImportTemplateCacheRunner::lockPath();
    file_put_contents($lockPath, json_encode([
        'started_at' => now()->subMinutes(40)->toIso8601String(),
        'pid' => \getmypid(),
    ], JSON_THROW_ON_ERROR));

    $this->get('/deploy/deploy-token/cache-import-templates/status')
        ->assertSuccessful()
        ->assertJson([
            'running' => false,
        ]);

    expect(ImportTemplateCacheRunner::isRunning())->toBeFalse();
});

test('step flow exports regions in chunks then builds templates until done', function () {
    $level = Level::factory()->create(['name' => 'SD']);

    $province = Province::factory()->create(['name' => 'Prov Step']);
    $city = City::factory()->create(['province_id' => $province->id, 'name' => 'Kota Step']);
    $subDistrict = SubDistrict::factory()->create(['city_id' => $city->id, 'name' => 'Kec Step']);

    foreach (['Desa A', 'Desa B', 'Desa C'] as $name) {
        Village::factory()->create(['sub_district_id' => $subDistrict->id, 'name' => $name]);
    }

    $exporter = app(ImportTemplateExporter::class);
    ImportTemplateCacheRunner::resetStepProgress($exporter);

    $first = ImportTemplateCacheRunner::step($exporter, rowBudget: 2);

    expect($first['phase'])->toBe('regions')
        ->and($first['done'])->toBeFalse()
        ->and($first['regions']['exported'])->toBe(2)
        ->and($first['regions']['done'])->toBeFalse();

    $second = ImportTemplateCacheRunner::step($exporter, rowBudget: 2);

    expect($second['phase'])->toBe('regions')
        ->and($second['regions']['done'])->toBeTrue()
        ->and($exporter->hasRegionsCsv())->toBeTrue();

    $third = ImportTemplateCacheRunner::step($exporter, rowBudget: 2);

    expect($third['phase'])->toBe('template')
        ->and($third['cached']['teacher'])->toBeTrue();

    $fourth = ImportTemplateCacheRunner::step($exporter, rowBudget: 2);

    expect($fourth['phase'])->toBe('template')
        ->and($fourth['cached']['student_sd'])->toBeTrue();

    $fifth = ImportTemplateCacheRunner::step($exporter, rowBudget: 2);

    expect($fifth['done'])->toBeTrue()
        ->and($fifth['phase'])->toBe('done')
        ->and($exporter->isCached('teacher'))->toBeTrue()
        ->and($exporter->isCached('student', $level->id))->toBeTrue();

    ImportTemplateCacheRunner::resetStepProgress($exporter);
});

test('step endpoint returns json progress and html auto-refresh page', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    $exporter = app(ImportTemplateExporter::class);
    ImportTemplateCacheRunner::resetStepProgress($exporter);

    $this->get('/deploy/deploy-token/cache-import-templates/step?json=1')
        ->assertSuccessful()
        ->assertJsonStructure(['done', 'busy', 'phase', 'message', 'regions', 'cached']);

    $response = $this->get('/deploy/deploy-token/cache-import-templates/step');

    $response->assertSuccessful();

    expect($response->headers->get('Content-Type'))->toContain('text/html');

    ImportTemplateCacheRunner::resetStepProgress($exporter);
});

test('step endpoint reset clears progress and redirects', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    $exporter = app(ImportTemplateExporter::class);
    $exporter->warm('teacher', null, includeFullRegions: false);

    expect($exporter->isCached('teacher'))->toBeTrue();

    $this->get('/deploy/deploy-token/cache-import-templates/step?reset=1')
        ->assertRedirect('/deploy/deploy-token/cache-import-templates/step');

    expect($exporter->isCached('teacher'))->toBeFalse()
        ->and(is_file(ImportTemplateCacheRunner::stepStatePath()))->toBeFalse();
});

test('step endpoint is forbidden with invalid token', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    $this->get('/deploy/invalid-token/cache-import-templates/step')
        ->assertForbidden();
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
