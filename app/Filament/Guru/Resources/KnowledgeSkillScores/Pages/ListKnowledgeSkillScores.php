<?php

namespace App\Filament\Guru\Resources\KnowledgeSkillScores\Pages;

use App\Filament\Guru\Resources\KnowledgeSkillScores\KnowledgeSkillScoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeSkillScores extends ListRecords
{
    protected static string $resource = KnowledgeSkillScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
