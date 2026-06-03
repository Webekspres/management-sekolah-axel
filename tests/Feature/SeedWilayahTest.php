<?php

use App\Models\User;
use Database\Seeders\IndonesianRegionSeeder;
use Illuminate\Support\Facades\Artisan;

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
        ->with('personalia:cache-import-templates')
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('OK');

    $this->actingAs($user)
        ->get(route('seed.wilayah', ['token' => 'test-token']))
        ->assertSuccessful()
        ->assertJson([
            'status' => 'ok',
            'message' => 'Seeder wilayah dijalankan.',
            'output' => 'OK',
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
        ->with('personalia:cache-import-templates')
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->twice()
        ->andReturn('OK');

    $this->get('/deploy/deploy-token/seed-wilayah')
        ->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'command' => 'db:seed --class=IndonesianRegionSeeder',
            'output' => 'OK',
        ]);
});

test('deploy cache import templates is forbidden with invalid token', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    $this->get('/deploy/invalid-token/cache-import-templates')
        ->assertForbidden();
});

test('deploy cache import templates runs with valid token', function () {
    config()->set('app.deploy_secret', 'deploy-token');

    Artisan::shouldReceive('call')
        ->once()
        ->with('personalia:cache-import-templates')
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('Template impor tersimpan.');

    $this->get('/deploy/deploy-token/cache-import-templates')
        ->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'command' => 'personalia:cache-import-templates',
            'output' => 'Template impor tersimpan.',
        ]);
});
