<?php

namespace App\Livewire;

use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class AcademicLevelSwitcher extends Component
{
    public ?string $active_academic_level_id = null;

    public bool $isLockedForStudent = false;

    public ?string $warningMessage = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->effectiveRole() === 'siswa_ortu') {
            $studentLevelId = $user->resolveStudentAcademicLevelId();

            if (! $studentLevelId) {
                $this->warningMessage = 'Akun belum terhubung ke data siswa pada jenjang yang sesuai.';
                $this->active_academic_level_id = null;
                session()->forget('active_academic_level_id');

                return;
            }

            $this->isLockedForStudent = true;
            $this->active_academic_level_id = $studentLevelId;
            session(['active_academic_level_id' => $this->active_academic_level_id]);

            return;
        }

        $this->active_academic_level_id = session('active_academic_level_id');

        if (! $this->active_academic_level_id) {
            $firstLevel = Level::query()->first();
            if ($firstLevel) {
                $this->active_academic_level_id = $firstLevel->id;
                session(['active_academic_level_id' => $this->active_academic_level_id]);
            }
        }
    }

    public function updatedActiveAcademicLevelId(string $value): void
    {
        if ($this->isLockedForStudent) {
            return;
        }

        session(['active_academic_level_id' => $value]);
        $this->redirect(request()->header('Referer') ?? '/');
    }

    public function render(): View
    {
        $levels = $this->isLockedForStudent && $this->active_academic_level_id
            ? Level::query()->whereKey($this->active_academic_level_id)->pluck('name', 'id')
            : Level::pluck('name', 'id');

        return view('livewire.academic-level-switcher', compact('levels'));
    }
}
