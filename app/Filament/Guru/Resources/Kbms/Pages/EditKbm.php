<?php

namespace App\Filament\Guru\Resources\Kbms\Pages;

use App\Filament\Guru\Resources\Kbms\KbmResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditKbm extends EditRecord
{
    protected static string $resource = KbmResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'status' => 'Laporan KBM hanya dapat diubah saat masih berstatus Draft.',
            ]);
        }

        return $data;
    }
}
