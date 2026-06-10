<?php

namespace App\Filament\Actions;

use App\Models\Student;
use App\Models\Teacher;
use App\Support\Import\ImportColumnCatalog;
use App\Support\Import\ImportColumnDefinition;
use App\Support\Import\ImportPersonaliaFileInspector;
use App\Support\Import\ImportPersonaliaPreValidator;
use App\Support\Import\XlsxToCsvConverter;
use Filament\Actions\ImportAction;
use Filament\Actions\Imports\ImportColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader as CsvReader;
use Livewire\Component as LivewireComponent;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportPersonaliaAction extends ImportAction
{
    /**
     * @var list<string>
     */
    private const ACCEPTED_FILE_TYPES = [
        'text/csv',
        'text/x-csv',
        'application/csv',
        'application/x-csv',
        'text/comma-separated-values',
        'text/x-comma-separated-values',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/octet-stream',
    ];

    /**
     * @var array<string, string>
     */
    private const MIME_TYPE_MAP = [
        'csv' => 'text/csv',
        'txt' => 'text/plain',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * @var 'student'|'teacher'
     */
    protected string $personaliaType = 'student';

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorize(function (): bool {
            $user = auth()->user();

            return $user?->can('create', Student::class)
                || $user?->can('create', Teacher::class);
        });

        $parentSchema = $this->schema;

        $this->schema(function (ImportAction $action) use ($parentSchema): array {
            /** @var array<int, Component> $components */
            $components = $this->evaluate($parentSchema, [
                'action' => $action,
            ]);

            $components = array_map(function ($component) use ($action) {
                if ($component instanceof FileUpload && $component->getName() === 'file') {
                    return $this->makeImportFileUpload($action);
                }

                return $component;
            }, $components);

            $components[] = Placeholder::make('import_preview')
                ->label(__('personalia.import.preview.label'))
                ->content(function (Get $get) use ($action): string {
                    $file = $get('file');

                    if (! $file instanceof TemporaryUploadedFile) {
                        return '';
                    }

                    $rowCount = ImportPersonaliaFileInspector::countDataRows($this, $file);
                    $missingColumns = ImportPersonaliaFileInspector::unmappedRequiredColumns(
                        $action->getImporter(),
                        $get('columnMap') ?? [],
                    );

                    $lines = [
                        __('personalia.import.preview.rows', ['count' => number_format($rowCount)]),
                    ];

                    if ($this->personaliaType === 'student' && blank(session('active_academic_level_id'))) {
                        $lines[] = __('personalia.import.preview.level_missing');
                    }

                    if ($missingColumns !== []) {
                        $lines[] = __('personalia.import.preview.unmapped_columns', [
                            'columns' => implode(', ', $missingColumns),
                        ]);
                    }

                    if ($rowCount > 0 && $missingColumns === []) {
                        $lines[] = __('personalia.import.preview.ready');
                    }

                    return implode("\n", $lines);
                })
                ->visible(fn (Get $get): bool => $get('file') instanceof TemporaryUploadedFile)
                ->columnSpanFull();

            return $components;
        });

        $this->beforeFormValidated(function (array $data, ImportAction $action): void {
            ImportPersonaliaPreValidator::validate(
                data: $data,
                personaliaType: $this->personaliaType,
                importerClass: $action->getImporter(),
                action: $this,
            );
        });

        $this->label(__('personalia.import.upload_data'));
        $this->modalHeading(__('personalia.import.upload_heading'));
        $this->modalSubmitActionLabel(__('personalia.import.upload_data'));
        $this->modalDescription(__('personalia.import.upload_helper'));
        $this->successNotificationTitle(__('personalia.import.notifications.processing_title'));

        $this->options(fn (): array => [
            'academic_level_id' => session('active_academic_level_id'),
        ]);
    }

    /**
     * @param  'student'|'teacher'  $type
     */
    public function personaliaType(string $type): static
    {
        $this->personaliaType = $type;

        return $this;
    }

    /**
     * @return resource | false
     */
    public function getUploadedFileStream(TemporaryUploadedFile $file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'xlsx') {
            $path = $file->getRealPath() ?: $file->getPathname();

            $csvPath = app(XlsxToCsvConverter::class)->convert(
                $path,
                $this->getColumnDefinitions(),
            );

            return fopen($csvPath, 'r');
        }

        return parent::getUploadedFileStream($file);
    }

    /**
     * @return array<mixed>
     */
    public function getFileValidationRules(): array
    {
        $rules = parent::getFileValidationRules();
        $rules[0] = 'extensions:csv,txt,xlsx';

        return $rules;
    }

    /**
     * @return array<int, ImportColumnDefinition>
     */
    protected function getColumnDefinitions(): array
    {
        return $this->personaliaType === 'teacher'
            ? ImportColumnCatalog::teacherColumns()
            : ImportColumnCatalog::studentColumns();
    }

    private function makeImportFileUpload(ImportAction $action): FileUpload
    {
        return FileUpload::make('file')
            ->label(__('filament-actions::import.modal.form.file.label'))
            ->placeholder(__('personalia.import.upload_placeholder'))
            ->acceptedFileTypes(self::ACCEPTED_FILE_TYPES)
            ->mimeTypeMap(self::MIME_TYPE_MAP)
            ->rules($action->getFileValidationRules())
            ->afterStateUpdated(function (FileUpload $component, LivewireComponent $livewire, Set $set, ?TemporaryUploadedFile $state) use ($action): void {
                if (! $state instanceof TemporaryUploadedFile) {
                    return;
                }

                try {
                    $livewire->validateOnly($component->getStatePath());
                } catch (ValidationException $exception) {
                    $component->state([]);

                    throw $exception;
                }

                $csvStream = $this->getUploadedFileStream($state);

                if (! $csvStream) {
                    return;
                }

                $csvReader = CsvReader::from($csvStream);

                if (filled($csvDelimiter = $this->getCsvDelimiter($csvReader))) {
                    $csvReader->setDelimiter($csvDelimiter);
                }

                $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);

                $csvColumns = $csvReader->getHeader();

                $lowercaseCsvColumnValues = array_map(Str::lower(...), $csvColumns);
                $lowercaseCsvColumnKeys = array_combine(
                    $lowercaseCsvColumnValues,
                    $csvColumns,
                );

                $set('columnMap', array_reduce($action->getImporter()::getColumns(), function (array $carry, ImportColumn $column) use ($lowercaseCsvColumnKeys, $lowercaseCsvColumnValues) {
                    $carry[$column->getName()] = $lowercaseCsvColumnKeys[
                        Arr::first(
                            array_intersect(
                                $lowercaseCsvColumnValues,
                                $column->getGuesses(),
                            ),
                        )
                    ] ?? null;

                    return $carry;
                }, []));
            })
            ->storeFiles(false)
            ->visibility('private')
            ->required()
            ->hiddenLabel();
    }
}
