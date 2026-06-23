<?php

/**
 * Web-based deployment routes for cPanel environments without terminal access.
 *
 * Usage (from browser):
 *   - Composer install (no Laravel boot): https://yourdomain.com/deploy-composer-install.php?token={DEPLOY_SECRET}
 *   - Migrate:       https://yourdomain.com/deploy/{token}/migrate
 *   - Seed wilayah:  https://yourdomain.com/deploy/{token}/seed-wilayah
 *   - Cache template impor (RECOMMENDED, shared-hosting safe):
 *     https://yourdomain.com/deploy/{token}/cache-import-templates/step
 *     Open in browser and leave the tab open - it auto-continues in small
 *     chunks until done. Add ?reset=1 to start over. Add ?json=1 for JSON.
 *   - Cache status: .../cache-import-templates/status
 *   - Cache sync (may timeout on shared hosting): .../cache-import-templates?sync=1
 *   - Cache one template: .../cache-import-templates?sync=1&target=teacher (or sd|smp|sma)
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
use App\Support\EnsurePublicStorageLink;
use App\Support\Import\ImportTemplateCacheRunner;
use App\Support\Import\ImportTemplateExporter;
use Database\Seeders\IndonesianRegionSeeder;
use Illuminate\Http\Request;
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

        $cacheResult = ImportTemplateCacheRunner::dispatchInBackground();

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'command' => 'db:seed --class=IndonesianRegionSeeder',
            'output' => $output,
            'cache_import_templates' => array_merge(
                ['command' => 'personalia:cache-import-templates'],
                $cacheResult,
            ),
        ], $exitCode === 0 ? 200 : 500);
    });

    Route::get('/cache-import-templates/status', function (string $token, ImportTemplateExporter $exporter) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        return response()->json(ImportTemplateCacheRunner::status($exporter));
    });

    Route::get('/cache-import-templates/step', function (string $token, Request $request, ImportTemplateExporter $exporter) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        if ($request->boolean('reset')) {
            ImportTemplateCacheRunner::resetStepProgress($exporter);

            return redirect()->to($request->url());
        }

        try {
            $result = ImportTemplateCacheRunner::step($exporter);
        } catch (Throwable $exception) {
            if ($request->boolean('json')) {
                return response()->json([
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                ], 500);
            }

            $error = e($exception->getMessage());

            return response(
                '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Cache Template - Error</title></head>'
                .'<body style="font-family:sans-serif;max-width:640px;margin:40px auto;">'
                ."<h2 style=\"color:#b91c1c;\">Terjadi kesalahan</h2><p>{$error}</p>"
                ."<p><a href=\"{$request->url()}\">Coba lagi</a> &middot; <a href=\"{$request->url()}?reset=1\">Reset &amp; mulai ulang</a></p>"
                .'</body></html>',
                500,
            )->header('Content-Type', 'text/html');
        }

        if ($request->boolean('json')) {
            return response()->json($result);
        }

        $cachedList = collect($result['cached'])
            ->map(fn (bool $isCached, string $name): string => '<li>'.e($name).': '.($isCached ? '&#9989; selesai' : '&#8987; belum').'</li>')
            ->implode('');

        $regions = $result['regions'];
        $regionsLine = $regions['done']
            ? 'Data wilayah: selesai ('.number_format($regions['exported'] > 0 ? $regions['exported'] : $regions['total']).' baris).'
            : 'Data wilayah: '.number_format($regions['exported']).' / '.number_format($regions['total']).' baris.';

        $refreshTag = $result['done'] ? '' : '<meta http-equiv="refresh" content="2">';
        $heading = $result['done'] ? 'Selesai!' : ($result['busy'] ? 'Menunggu proses lain...' : 'Sedang membangun...');
        $footer = $result['done']
            ? '<p style="color:#15803d;font-weight:bold;">Semua template siap. Tab ini boleh ditutup.</p>'
            : '<p>Biarkan tab ini terbuka - halaman akan lanjut otomatis setiap 2 detik.</p>';

        return response(
            "<!DOCTYPE html><html lang=\"id\"><head><meta charset=\"utf-8\">{$refreshTag}<title>Cache Template Impor</title></head>"
            .'<body style="font-family:sans-serif;max-width:640px;margin:40px auto;">'
            ."<h2>{$heading}</h2>"
            .'<p>'.e($result['message']).'</p>'
            ."<p>{$regionsLine}</p>"
            ."<ul>{$cachedList}</ul>"
            .$footer
            ."<p style=\"color:#6b7280;font-size:13px;\"><a href=\"{$request->url()}?reset=1\">Reset &amp; mulai ulang dari awal</a></p>"
            .'</body></html>',
        )->header('Content-Type', 'text/html');
    });

    Route::get('/cache-import-templates', function (string $token, Request $request) {
        abort_unless($token === config('app.deploy_secret'), 403, 'Invalid deploy token.');

        try {
            $target = $request->query('target');

            if ($request->boolean('force')) {
                ImportTemplateCacheRunner::releaseLock();
            }

            if ($request->boolean('sync')) {
                $result = ImportTemplateCacheRunner::runSynchronously(
                    is_string($target) && $target !== '' ? $target : null,
                );

                return response()->json(array_merge(
                    ['command' => 'personalia:cache-import-templates'],
                    $result,
                ), $result['exit_code'] === 0 ? 200 : 500);
            }

            $result = ImportTemplateCacheRunner::dispatchInBackground(
                is_string($target) && $target !== '' ? $target : null,
            );

            return response()->json(array_merge(
                ['command' => 'personalia:cache-import-templates'],
                $result,
            ), in_array($result['status'], ['started', 'running'], true) ? 202 : 500);
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

        $linked = EnsurePublicStorageLink::run();

        return response()->json([
            'status' => $linked ? 'success' : 'error',
            'command' => 'storage:link',
            'linked' => $linked,
            'public_symlink' => is_link(public_path('storage')),
            'resolved_target' => realpath(storage_path('app/public')) ?: null,
            'resolved_link' => is_link(public_path('storage')) ? (realpath(public_path('storage')) ?: null) : null,
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

        $discoverExit = 0;
        $discoverOutput = '';

        if ($composerResult->successful()) {
            $discoverExit = Artisan::call('package:discover', ['--ansi' => true]);
            $discoverOutput = Artisan::output();
        }

        $success = $composerResult->successful() && $discoverExit === 0;

        return response()->json([
            'status' => $success ? 'success' : 'error',
            'composer' => [
                'exit_code' => $composerResult->exitCode(),
                'output' => $composerResult->output(),
                'error_output' => $composerResult->errorOutput(),
            ],
            'package_discover' => [
                'exit_code' => $discoverExit,
                'output' => $discoverOutput,
            ],
        ], $success ? 200 : 500);
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

        $discoverExit = 0;
        $discoverOutput = '';

        if ($composerResult->successful()) {
            $discoverExit = Artisan::call('package:discover', ['--ansi' => true]);
            $discoverOutput = Artisan::output();
        }

        $migrateExit = Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        $storageLinked = EnsurePublicStorageLink::run();

        $cacheExit = 0;
        $cacheOutput = '';

        $cacheResult = ['status' => 'skipped', 'mode' => 'background', 'message' => 'Composer atau package discover gagal.'];

        if ($composerResult->successful() && $discoverExit === 0) {
            try {
                $cacheResult = ImportTemplateCacheRunner::dispatchInBackground();
            } catch (Throwable $exception) {
                $cacheResult = [
                    'status' => 'error',
                    'mode' => 'background',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        Artisan::call('optimize');
        $optimizeOutput = Artisan::output();

        $success = $composerResult->successful()
            && $discoverExit === 0
            && $migrateExit === 0
            && in_array($cacheResult['status'], ['started', 'running', 'skipped'], true);

        return response()->json([
            'status' => $success ? 'success' : 'error',
            'composer' => [
                'exit_code' => $composerResult->exitCode(),
                'output' => $composerResult->output(),
                'error_output' => $composerResult->errorOutput(),
            ],
            'package_discover' => [
                'exit_code' => $discoverExit,
                'output' => $discoverOutput,
            ],
            'migrate' => [
                'exit_code' => $migrateExit,
                'output' => $migrateOutput,
            ],
            'storage_link' => [
                'linked' => $storageLinked,
            ],
            'cache_import_templates' => array_merge(
                ['command' => 'personalia:cache-import-templates'],
                $cacheResult,
            ),
            'optimize' => [
                'output' => $optimizeOutput,
            ],
        ], $success ? 200 : 500);
    });

});
