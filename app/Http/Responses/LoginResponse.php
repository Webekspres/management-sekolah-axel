<?php

namespace App\Http\Responses;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        /** @var User $user */
        $user = auth()->user();
        $effectiveRole = $user->effectiveRole();

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
