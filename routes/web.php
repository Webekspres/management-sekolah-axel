<?php

use App\Enums\UserRole;
use App\Http\Controllers\DownloadImportFailureXlsxController;
use App\Http\Controllers\NotificationPollController;
use App\Http\Controllers\PersonaliaImportTemplateController;
use App\Models\Rapor;
use App\Models\User;
use App\Support\Import\ImportTemplateCacheRunner;
use Database\Seeders\IndonesianRegionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    if (auth()->check()) {
        /** @var User $user */
        $user = auth()->user();

        return match ($user->userRole()) {
            UserRole::SuperAdmin => redirect()->to('/admin'),
            UserRole::KepalaSekolah => redirect()->to('/kepsek'),
            UserRole::Guru => redirect()->to('/guru'),
            UserRole::SiswaOrtu => redirect()->to('/student'),
        };
    }

    return redirect()->to('/login');
});

// ──────────────────────────────────────────────
// Notification Polling (realtime via Alpine)
// ──────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications/poll', NotificationPollController::class)
        ->name('notifications.poll');
    Route::post('/notifications/{id}/read', [NotificationPollController::class, 'markAsRead'])
        ->name('notifications.mark-as-read');
    Route::post('/notifications/read-all', [NotificationPollController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-as-read');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/personalia/import-template/{type}', PersonaliaImportTemplateController::class)
        ->name('personalia.import-template');

    Route::get('/personalia/imports/{import}/failed-rows/download', DownloadImportFailureXlsxController::class)
        ->name('personalia.imports.failed-rows.download');

    Route::get('/rapor/{rapor}/download', function (Rapor $rapor) {
        Gate::authorize('download', $rapor);

        if (! $rapor->file_path || ! Storage::exists($rapor->file_path)) {
            abort(404, 'File rapor tidak ditemukan.');
        }

        return Storage::download(
            $rapor->file_path,
            'rapor-'.str($rapor->student?->user?->name.'-'.$rapor->academicYear?->name)
                ->slug()
                ->append('.pdf')
                ->toString()
        );
    })->name('rapor.download');

    Route::get('/internal/seed/wilayah', function (Request $request) {
        /** @var User|null $user */
        $user = $request->user();

        $expectedToken = (string) config('app.deploy_secret');
        $token = (string) $request->query('token', '');

        abort_unless($expectedToken !== '', 500, 'Deploy token belum dikonfigurasi.');
        abort_unless(hash_equals($expectedToken, $token), 403, 'Invalid token.');

        abort_unless($user?->hasUserRole(UserRole::SuperAdmin), 403);

        Artisan::call('db:seed', [
            '--class' => IndonesianRegionSeeder::class,
            '--force' => true,
        ]);

        $cacheResult = ImportTemplateCacheRunner::dispatchInBackground();

        return response()->json([
            'status' => 'ok',
            'message' => 'Seeder wilayah dijalankan.',
            'output' => trim(Artisan::output()),
            'cache_import_templates' => $cacheResult,
        ]);
    })->middleware('throttle:1,1')->name('seed.wilayah');
});
