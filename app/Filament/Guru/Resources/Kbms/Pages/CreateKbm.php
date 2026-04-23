<?php

namespace App\Filament\Guru\Resources\Kbms\Pages;

use App\Filament\Guru\Resources\Kbms\KbmResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKbm extends CreateRecord
{
    protected static string $resource = KbmResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'DRAFT';
        $data['revision_note'] = null;

        return $data;
    }
}
