<?php

use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\User;
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
});
