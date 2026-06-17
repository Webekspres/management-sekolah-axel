<?php

namespace App\Filament\Guru\Resources\Kbms\Pages;

use App\Filament\Guru\Resources\Kbms\KbmResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKbms extends ListRecords
{
    protected static string $resource = KbmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
