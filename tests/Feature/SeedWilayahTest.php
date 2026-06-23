<?php

use App\Models\User;
use App\Support\Import\ImportTemplateCacheRunner;
use Database\Seeders\IndonesianRegionSeeder;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    ImportTemplateCacheRunner::releaseLock();
});

afterEach(function () {
    ImportTemplateCacheRunner::releaseLock();
});

test('wilayah seeder is forbidden for non super admin', function () {
    $user = User::factory()->asGuru()->create();

    config()->set('app.deploy_secret', 'test-token');

    $this->actingAs($user)
        ->get(route('seed.wilayah', ['token' => 'test-token']))
        ->assertForbidden();
});

test('wilayah seeder is forbidden with invalid token', function () {
    $user = User::factory()->asAdmin()->create();

    config()->set('app.deploy_secret', 'test-token');

    $this->actingAs($user)
        ->get(route('seed.wilayah', ['token' => 'invalid-token']))
        ->assertForbidden();
});

test('wilayah seeder runs for super admin', function () {
    $user = User::factory()->asAdmin()->create();

    config()->set('app.deploy_secret', 'test-token');

    Artisan::shouldReceive('call')
        ->once()
        ->with('db:seed', [
            '--class' => IndonesianRegionSeeder::class,
            '--force' => true,
        ])
        ->andReturn(0);

    Artisan::shouldReceive('call')
        ->once()
        ->with('personalia:cache-import-templates', [])
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->andReturn('OK');

    $this->actingAs($user)
        ->get(route('seed.wilayah', ['token' => 'test-token']))
        ->assertSuccessful()
        ->assertJson([
            'status' => 'ok',
            'message' => 'Seeder wilayah dijalankan.',
            'output' => 'OK',
            'cache_import_templates' => [
                'mode' => 'after_response',
            ],
        ]);
});

test('deploy seed wilayah is forbidden with invalid token', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    $this->get('/deploy/invalid-token/seed-wilayah')
        ->assertForbidden();
});

test('deploy seed wilayah runs with valid token', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    Artisan::shouldReceive('call')
        ->once()
        ->with('db:seed', [
            '--class' => IndonesianRegionSeeder::class,
            '--force' => true,
        ])
        ->andReturn(0);

    Artisan::shouldReceive('call')
        ->once()
        ->with('personalia:cache-import-templates', [])
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->andReturn('OK');

    $this->get('/deploy/deploy-token/seed-wilayah')
        ->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'command' => 'db:seed --class=IndonesianRegionSeeder',
            'output' => 'OK',
            'cache_import_templates' => [
                'command' => 'personalia:cache-import-templates',
                'mode' => 'after_response',
            ],
        ]);
});

test('deploy cache import templates is forbidden with invalid token', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    $this->get('/deploy/invalid-token/cache-import-templates')
        ->assertForbidden();
});

test('deploy cache import templates starts after http response by default', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    Artisan::shouldReceive('call')
        ->once()
        ->with('personalia:cache-import-templates', [])
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->andReturn('Template impor tersimpan.');

    $this->get('/deploy/deploy-token/cache-import-templates')
        ->assertAccepted()
        ->assertJson([
            'command' => 'personalia:cache-import-templates',
            'mode' => 'after_response',
        ]);
});

test('deploy cache import templates can run synchronously with sync flag', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    Artisan::shouldReceive('call')
        ->once()
        ->with('personalia:cache-import-templates', [])
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('Template impor tersimpan.');

    $this->get('/deploy/deploy-token/cache-import-templates?sync=1')
        ->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'command' => 'personalia:cache-import-templates',
            'mode' => 'sync',
            'output' => 'Template impor tersimpan.',
        ]);
});

test('deploy release is forbidden with invalid token', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    $this->get('/deploy/invalid-token/release')
        ->assertForbidden();
});

test('deploy release runs migrate and optimize with valid token', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    Process::fake([
        '*' => function () {
            return new FakeProcessResult(
                command: '',
                exitCode: 0,
                output: 'Composer dependencies installed.',
            );
        },
    ]);

    Artisan::shouldReceive('call')
        ->once()
        ->with('route:clear')
        ->andReturn(0);

    Artisan::shouldReceive('call')
        ->once()
        ->with('package:discover', ['--ansi' => true])
        ->andReturn(0);

    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andReturn(0);

    Artisan::shouldReceive('call')
        ->once()
        ->with('config:clear')
        ->andReturn(0);

    Artisan::shouldReceive('call')
        ->once()
        ->with('optimize')
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->times(3)
        ->andReturn('OK');

    $this->get('/deploy/deploy-token/release')
        ->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'composer' => [
                'exit_code' => 0,
                'output' => "Composer dependencies installed.\n",
            ],
            'package_discover' => [
                'exit_code' => 0,
                'output' => 'OK',
            ],
            'migrate' => [
                'exit_code' => 0,
                'output' => 'OK',
            ],
            'cache_import_templates' => [
                'command' => 'personalia:cache-import-templates',
                'mode' => 'after_response',
            ],
            'optimize' => [
                'output' => 'OK',
            ],
        ]);
});
