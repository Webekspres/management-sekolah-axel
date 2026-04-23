<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

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
