<?php

namespace App\Filament\Student\Pages;

use App\Models\Rapor;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class MyRaporPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $navigationLabel = 'Rapor Saya';

    protected static ?string $title = 'Rapor Saya';

    protected static ?string $slug = 'rapor-saya';

    protected string $view = 'filament.student.pages.my-rapor-page';

    /** @var Collection<int, Rapor> */
    public Collection $rapors;

    public bool $hasStudentProfile = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;

        if (! $student) {
            $this->hasStudentProfile = false;
            $this->rapors = collect();

            return;
        }

        $this->hasStudentProfile = true;

        $this->rapors = Rapor::where('student_id', $student->id)
            ->with('academicYear')
            ->orderByDesc('academic_year_id')
            ->get();
    }

    public function downloadRapor(string $raporId): StreamedResponse|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;

        $rapor = Rapor::where('id', $raporId)
            ->where('student_id', $student?->id)
            ->where('status', 'APPROVED')
            ->firstOrFail();

        if (! $rapor->file_path || ! Storage::exists($rapor->file_path)) {
            session()->flash('error', 'File rapor tidak ditemukan.');

            return redirect()->back();
        }

        return Storage::download($rapor->file_path);
    }
}
