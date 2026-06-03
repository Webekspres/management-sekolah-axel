<?php

use App\Http\Controllers\PersonaliaImportTemplateController;
use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\IndonesianRegionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    if (auth()->check()) {
        /** @var User $user */
        $user = auth()->user();

        return match ($user->role) {
            'super_admin' => redirect()->to('/admin'),
            'kepala_sekolah' => redirect()->to('/kepsek'),
            'guru' => redirect()->to('/guru'),
            'siswa_ortu' => redirect()->to('/student'),
            default => redirect()->to('/admin'),
        };
    }

    return redirect()->to('/login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/personalia/import-template/{type}', PersonaliaImportTemplateController::class)
        ->name('personalia.import-template');

    Route::get('/rapor/{rapor}/download', function (Rapor $rapor) {
        /** @var User $user */
        $user = auth()->user();

        // Authorization check
        $canDownload = match ($user->role) {
            'super_admin' => true,
            'kepala_sekolah' => true,
            'guru' => $user->teacher && SchoolClass::where('teacher_id', $user->teacher->id)
                ->whereHas('students', fn ($q) => $q->where('id', $rapor->student_id))
                ->exists(),
            'siswa_ortu' => $policy->download($user, $rapor),
            default => false,
        };

        if (! $canDownload) {
            abort(403);
        }

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

        abort_unless($user?->role === 'super_admin', 403);

        Artisan::call('db:seed', [
            '--class' => IndonesianRegionSeeder::class,
            '--force' => true,
        ]);

        Artisan::call('personalia:cache-import-templates');

        return response()->json([
            'status' => 'ok',
            'message' => 'Seeder wilayah dijalankan.',
            'output' => trim(Artisan::output()),
            'templates' => 'Template impor personalia di-cache ulang.',
        ]);
    })->middleware('throttle:1,1')->name('seed.wilayah');
});
