<?php

namespace App\Filament\Actions;

use App\Support\Import\ImportColumnCatalog;
use App\Support\Import\ImportColumnDefinition;
use App\Support\Import\XlsxToCsvConverter;
use Filament\Actions\ImportAction;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportPersonaliaAction extends ImportAction
{
    /**
     * @var 'student'|'teacher'
     */
    protected string $personaliaType = 'student';

    protected function setUp(): void
    {
        parent::setUp();

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
