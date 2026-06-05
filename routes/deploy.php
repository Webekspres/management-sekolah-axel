<?php

/**
 * Web-based deployment routes for cPanel environments without terminal access.
 *
 * Usage (from browser):
 *   - Migrate:       https://yourdomain.com/deploy/{token}/migrate
 *   - Seed wilayah:  https://yourdomain.com/deploy/{token}/seed-wilayah
 *   - Cache template impor: https://yourdomain.com/deploy/{token}/cache-import-templates
 *   - Migrate+Seed:  https://yourdomain.com/deploy/{token}/migrate-seed
 *   - Create user:   https://yourdomain.com/deploy/{token}/create-user?name=Admin&email=admin@hstkb.sch.id&password=secret123&role=super_admin
 *   - Link storage:  https://yourdomain.com/deploy/{token}/storage-link
 *   - Optimize:      https://yourdomain.com/deploy/{token}/optimize
 *   - Access policies: https://yourdomain.com/deploy/{token}/seed-access-policies
 *   - Release:       https://yourdomain.com/deploy/{token}/release (composer + migrate + optimize)
 *
 * Set DEPLOY_SECRET in your .env file to a long random string.
 * REMOVE or change DEPLOY_SECRET after deployment is done!
 */

use App\Models\User;
use App\Support\AccessPolicyRegistry;
use App\Support\ComposerInstallRunner;
use Database\Seeders\IndonesianRegionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::prefix('deploy/{token}')->group(function () {

    Route::get('/migrate', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        $exitCode = Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'command' => 'migrate',
            'output' => $output,
        ]);
    });

    Route::get('/seed', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        $exitCode = Artisan::call('db:seed', [
            '--class' => 'Database\Seeders\ProductionSeeder',
            '--force' => true,
        ]);
        $output = Artisan::output();

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'command' => 'db:seed --class=ProductionSeeder',
            'output' => $output,
        ]);
    });

    Route::get('/seed-wilayah', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        $exitCode = Artisan::call('db:seed', [
            '--class' => IndonesianRegionSeeder::class,
            '--force' => true,
        ]);
        $output = Artisan::output();

        $cacheExit = Artisan::call('personalia:cache-import-templates');
        $cacheOutput = Artisan::output();

        return response()->json([
            'status' => ($exitCode === 0 && $cacheExit === 0) ? 'success' : 'error',
            'command' => 'db:seed --class=IndonesianRegionSeeder',
            'output' => $output,
            'cache_import_templates' => [
                'exit_code' => $cacheExit,
                'command' => 'personalia:cache-import-templates',
                'output' => $cacheOutput,
            ],
        ]);
    });

    Route::get('/cache-import-templates', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        try {
            File::ensureDirectoryExists(storage_path('app/import-templates'));

            $exitCode = Artisan::call('personalia:cache-import-templates');
            $output = Artisan::output();

            return response()->json([
                'status' => $exitCode === 0 ? 'success' : 'error',
                'command' => 'personalia:cache-import-templates',
                'output' => $output,
            ], $exitCode === 0 ? 200 : 500);
        } catch (Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'command' => 'personalia:cache-import-templates',
                'message' => $exception->getMessage(),
            ], 500);
        }
    });

    Route::get('/migrate-seed', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        $migrateExit = Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        $seedExit = Artisan::call('db:seed', [
            '--class' => 'Database\Seeders\ProductionSeeder',
            '--force' => true,
        ]);
        $seedOutput = Artisan::output();

        return response()->json([
            'status' => ($migrateExit === 0 && $seedExit === 0) ? 'success' : 'error',
            'migrate' => ['exit_code' => $migrateExit, 'output' => $migrateOutput],
            'seed' => ['exit_code' => $seedExit, 'output' => $seedOutput],
        ]);
    });

    Route::get('/create-user', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        $name = request()->query('name', 'Super Admin');
        $email = request()->query('email', 'admin@hstkb.sch.id');
        $password = request()->query('password', 'adminportal2026');
        $role = request()->query('role', 'super_admin');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => $role,
                'gender' => 'L',
                'is_active' => true,
                'email_verified_at' => now(),
                'phone_number' => '081234567890',
                'place_of_birth' => 'Jakarta',
                'date_of_birth' => '1990-01-01',
            ],
        );

        return response()->json([
            'status' => 'success',
            'message' => "User '{$user->name}' ({$user->email}) created/updated with role '{$role}'.",
            'user_id' => $user->id,
        ]);
    });

    Route::get('/storage-link', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        $exitCode = Artisan::call('storage:link', ['--force' => true]);
        $output = Artisan::output();

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'command' => 'storage:link',
            'output' => $output,
        ]);
    });

    Route::get('/seed-access-policies', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        AccessPolicyRegistry::sync();

        return response()->json([
            'status' => 'success',
            'command' => 'AccessPolicyRegistry::sync',
            'policies' => count(AccessPolicyRegistry::definitions()),
        ]);
    });

    Route::get('/composer-install', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        $composerResult = ComposerInstallRunner::run();

        return response()->json([
            'status' => $composerResult->successful() ? 'success' : 'error',
            'composer' => [
                'exit_code' => $composerResult->exitCode(),
                'output' => $composerResult->output(),
                'error_output' => $composerResult->errorOutput(),
            ],
        ]);
    });

    Route::get('/optimize', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        Artisan::call('route:clear');

        Artisan::call('optimize');
        $optimizeOutput = Artisan::output();

        return response()->json([
            'status' => 'success',
            'command' => 'optimize',
            'output' => $optimizeOutput,
        ]);
    });

    Route::get('/release', function (string $token) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        Artisan::call('route:clear');

        $composerResult = ComposerInstallRunner::run();

        $migrateExit = Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        $cacheExit = 0;
        $cacheOutput = '';

        if ($composerResult->successful()) {
            try {
                File::ensureDirectoryExists(storage_path('app/import-templates'));
                $cacheExit = Artisan::call('personalia:cache-import-templates');
                $cacheOutput = Artisan::output();
            } catch (Throwable $exception) {
                $cacheExit = 1;
                $cacheOutput = $exception->getMessage();
            }
        }

        Artisan::call('optimize');
        $optimizeOutput = Artisan::output();

        $success = $composerResult->successful() && $migrateExit === 0 && $cacheExit === 0;

        return response()->json([
            'status' => $success ? 'success' : 'error',
            'composer' => [
                'exit_code' => $composerResult->exitCode(),
                'output' => $composerResult->output(),
                'error_output' => $composerResult->errorOutput(),
            ],
            'migrate' => [
                'exit_code' => $migrateExit,
                'output' => $migrateOutput,
            ],
            'cache_import_templates' => [
                'exit_code' => $cacheExit,
                'output' => $cacheOutput,
            ],
            'optimize' => [
                'output' => $optimizeOutput,
            ],
        ]);
    });

});
