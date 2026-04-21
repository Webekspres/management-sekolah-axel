<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    public function form(Schema $schema): Schema{
        $schema = TeacherForm::configure($schema);

        $schema->components([
            ...$schema->getComponents(),
            Section::make("Informasi Sistem")
                ->collapsible()
                ->schema([
                    Placeholder::make('created_at')
                        ->label('Dibuat')
                        ->content(fn () => $this->record->created_at?->format('d M Y H:i') ?? '-'),
                    Placeholder::make('updated_at')
                        ->label('Diperbarui')
                        ->content(fn () => $this->record->updated_at?->format('d M Y H:i') ?? '-'),
                ]),
        ]);

        return $schema;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
