<?php

namespace App\Filament\Actions;

use App\Support\Import\ImportColumnCatalog;
use App\Support\Import\ImportColumnDefinition;
use App\Support\Import\XlsxToCsvConverter;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;
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
    ];

    /**
     * @var 'student'|'teacher'
     */
    protected string $personaliaType = 'student';

    protected function setUp(): void
    {
        parent::setUp();

        $parentSchema = $this->schema;

        $this->schema(function (ImportAction $action) use ($parentSchema): array {
            /** @var array<int, Component> $components */
            $components = $this->evaluate($parentSchema, [
                'action' => $action,
            ]);

            return array_map(function ($component) {
                if ($component instanceof FileUpload && $component->getName() === 'file') {
                    return $component
                        ->acceptedFileTypes(self::ACCEPTED_FILE_TYPES)
                        ->placeholder(__('personalia.import.upload_placeholder'));
                }

                return $component;
            }, $components);
        });

        $this->label(__('personalia.import.upload_data'));
        $this->modalHeading(__('personalia.import.upload_heading'));
        $this->modalSubmitActionLabel(__('personalia.import.upload_data'));
        $this->modalDescription(__('personalia.import.upload_helper'));

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
            $csvPath = app(XlsxToCsvConverter::class)->convert(
                $file->getRealPath(),
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
}
