<?php

namespace App\Filament\Guru\Resources\KnowledgeSkillScores\Pages;

use App\Filament\Guru\Resources\KnowledgeSkillScores\KnowledgeSkillScoreResource;
use App\Services\RaporService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeSkillScore extends EditRecord
{
    protected static string $resource = KnowledgeSkillScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $raporService = app(RaporService::class);

        if (isset($data['knowledge_score']) && $data['knowledge_score'] !== null) {
            $data['knowledge_predicate'] = $raporService->assignPredicate((float) $data['knowledge_score']);
        }

        if (isset($data['skill_score']) && $data['skill_score'] !== null) {
            $data['skill_predicate'] = $raporService->assignPredicate((float) $data['skill_score']);
        }

        return $data;
    }
}
