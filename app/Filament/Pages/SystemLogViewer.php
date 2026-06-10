<?php

namespace App\Filament\Pages;

use App\DataTransferObjects\LogEntry;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\LogFileParser;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SystemLogViewer extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'System Log';

    protected static ?string $title = 'System Log';

    protected static ?string $slug = 'system-log';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.system-log-viewer';

    public ?string $selectedLogFile = null;

    /**
     * @var array{selectedLogFile: string|null}
     */
    public ?array $data = [];

    protected LogFileParser $logFileParser;

    public function boot(): void
    {
        $this->logFileParser = app(LogFileParser::class);
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->hasUserRole(UserRole::SuperAdmin) ?? false;
    }

    public function mount(): void
    {
        $logFiles = $this->logFileParser->detectLogFiles();
        $this->selectedLogFile = $logFiles[0] ?? null;

        $this->form->fill([
            'selectedLogFile' => $this->selectedLogFile,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Select::make('selectedLogFile')
                    ->label('Log File')
                    ->options(fn (): array => $this->getLogFileOptions())
                    ->live()
                    ->afterStateUpdated(function (string $state): void {
                        $this->selectedLogFile = $state;
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search, array $filters, int $page, int $recordsPerPage): LengthAwarePaginator {
                if ($this->selectedLogFile === null) {
                    return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
                }

                $entries = $this->logFileParser->parseLogFile($this->selectedLogFile);

                // Apply level filter
                $selectedLevel = $filters['level']['value'] ?? null;
                if (filled($selectedLevel)) {
                    $entries = $entries->filter(
                        fn (LogEntry $entry): bool => $entry->level === $selectedLevel
                    );
                }

                // Apply search filter (case-insensitive on message)
                if (filled($search)) {
                    $entries = $entries->filter(
                        fn (LogEntry $entry): bool => str_contains(
                            Str::lower($entry->message),
                            Str::lower($search)
                        )
                    );
                }

                // Sort from newest to oldest
                $entries = $entries->reverse()->values();

                $total = $entries->count();
                $offset = ($page - 1) * $recordsPerPage;
                $items = $entries->slice($offset, $recordsPerPage)
                    ->map(fn (LogEntry $entry): array => [
                        'timestamp' => $entry->timestamp,
                        'level' => $entry->level,
                        'environment' => $entry->environment,
                        'message' => $entry->message,
                        'context' => $entry->context,
                    ])
                    ->values();

                return new LengthAwarePaginator(
                    items: $items,
                    total: $total,
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns([
                TextColumn::make('timestamp')
                    ->label('Timestamp')
                    ->sortable(false),

                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'emergency', 'alert', 'critical', 'error' => 'danger',
                        'warning' => 'warning',
                        'notice', 'info' => 'info',
                        'debug' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('environment')
                    ->label('Environment')
                    ->sortable(false),

                TextColumn::make('message')
                    ->label('Message')
                    ->wrap()
                    ->limit(100),

                TextColumn::make('context')
                    ->label('Stack Trace')
                    ->wrap()
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('Log Level')
                    ->options([
                        'emergency' => 'EMERGENCY',
                        'alert' => 'ALERT',
                        'critical' => 'CRITICAL',
                        'error' => 'ERROR',
                        'warning' => 'WARNING',
                        'notice' => 'NOTICE',
                        'info' => 'INFO',
                        'debug' => 'DEBUG',
                    ]),
            ])
            ->searchable()
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon(Heroicon::OutlinedDocumentText)
            ->emptyStateHeading('Tidak ada log')
            ->emptyStateDescription(function (): string {
                if ($this->selectedLogFile === null) {
                    return 'Tidak ada file log yang tersedia. Log akan muncul di sini setelah aplikasi menghasilkannya.';
                }

                $entries = $this->logFileParser->parseLogFile($this->selectedLogFile);

                if ($entries->isEmpty()) {
                    return 'File log ini kosong.';
                }

                return 'Tidak ada entri log yang sesuai dengan kriteria pencarian Anda.';
            });
    }

    /**
     * @return array<string, string>
     */
    private function getLogFileOptions(): array
    {
        $files = $this->logFileParser->detectLogFiles();

        return collect($files)
            ->mapWithKeys(fn (string $file): array => [$file => $file])
            ->all();
    }

    public function hasMultipleLogFiles(): bool
    {
        return count($this->logFileParser->detectLogFiles()) > 1;
    }
}
