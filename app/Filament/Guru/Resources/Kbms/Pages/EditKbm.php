<?php

namespace App\Filament\Guru\Resources\Kbms\Pages;

use App\Filament\Guru\Resources\Kbms\KbmResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditKbm extends EditRecord
{
    protected static string $resource = KbmResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! in_array($this->record->status, ['DRAFT', 'REVISED'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Laporan KBM dengan status saat ini tidak dapat diubah.',
            ]);
        }

        return $data;
    }
}
