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

        if ($user->role === 'super_admin') {
            return redirect()->to('/admin');
        } elseif ($user->role === 'kepala_sekolah') {
            return redirect()->to('/kepsek');
        } elseif ($user->role === 'guru') {
            return redirect()->to('/guru');
        } elseif ($user->role === 'siswa_ortu') {
            return redirect()->to('/student');
        }

        return redirect()->to('/admin');
    }
}
