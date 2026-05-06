<?php

namespace App\Http\Responses;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        /** @var User $user */
        $user = Auth::user();
        $effectiveRole = $user->effectiveRole();

        if ($effectiveRole === 'siswa_ortu') {
            $studentLevelId = $user->resolveStudentAcademicLevelId();

            if ($studentLevelId) {
                session(['active_academic_level_id' => $studentLevelId]);
            } else {
                session()->forget('active_academic_level_id');
            }
        }

        if ($effectiveRole === 'super_admin') {
            return redirect()->to('/admin');
        } elseif ($effectiveRole === 'kepala_sekolah') {
            return redirect()->to('/kepsek');
        } elseif ($effectiveRole === 'guru') {
            return redirect()->to('/guru');
        } elseif ($effectiveRole === 'siswa_ortu') {
            return redirect()->to('/student');
        }

        return redirect()->to('/admin');
    }
}
