<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Widgets\RaporStatsWidget;
use App\Models\Rapor;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class MyRaporPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $navigationLabel = 'Rapor Saya';

    protected static ?string $title = 'Rapor Saya';

    protected static ?string $slug = 'rapor-saya';

    protected string $view = 'filament.student.pages.my-rapor-page';

    public bool $hasStudentProfile = false;

    public function getHeaderWidgets(): array
    {
        return [RaporStatsWidget::class];
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;

        if (! $student) {
            $this->hasStudentProfile = false;

            return;
        }

        $this->hasStudentProfile = true;
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = auth()->user();
        $studentId = $user->student?->id;

        return $table
            ->query(
                Rapor::query()
                    ->where('student_id', $studentId)
                    ->with('academicYear')
                    ->orderByDesc('academic_year_id')
            )
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Tahun Akademik')
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('academicYear.semester')
                    ->label('Semester')
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (string $state): string => match ($state) {
                        'APPROVED' => 'success',
                        'FINALIZED' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'APPROVED' => 'Disetujui',
                        'FINALIZED' => 'Terfinalisasi',
                        default => 'Draft',
                    }),
                TextColumn::make('approved_at')
                    ->label('Tanggal Disetujui')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->alignCenter(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('primary')
                    ->visible(fn (Rapor $record): bool => $record->isApproved() && filled($record->file_path))
                    ->action(fn (Rapor $record) => $this->downloadRapor($record->id)),
            ])
            ->emptyStateHeading('Belum ada rapor')
            ->emptyStateDescription('Rapor Anda belum tersedia.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText)
            ->paginated(false);
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
