<?php

namespace App\Filament\Guru\Resources\KnowledgeSkillScores;

use App\Filament\Guru\Resources\KnowledgeSkillScores\Pages\CreateKnowledgeSkillScore;
use App\Filament\Guru\Resources\KnowledgeSkillScores\Pages\EditKnowledgeSkillScore;
use App\Filament\Guru\Resources\KnowledgeSkillScores\Pages\ListKnowledgeSkillScores;
use App\Filament\Guru\Resources\KnowledgeSkillScores\Schemas\KnowledgeSkillScoreForm;
use App\Filament\Guru\Resources\KnowledgeSkillScores\Tables\KnowledgeSkillScoresTable;
use App\Models\KnowledgeSkillScore;
use App\Models\Schedule;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class KnowledgeSkillScoreResource extends Resource
{
    protected static ?string $model = KnowledgeSkillScore::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static UnitEnum|string|null $navigationGroup = 'Penilaian';

    protected static ?string $label = 'Nilai Pengetahuan & Keterampilan';

    protected static ?string $pluralLabel = 'Nilai Pengetahuan & Keterampilan';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->can('viewAny', KnowledgeSkillScore::class) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return KnowledgeSkillScoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnowledgeSkillScoresTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student.user', 'subject', 'academicYear']);

        /** @var User $user */
        $user = auth()->user();

        if (! $user?->teacher) {
            return $query->whereRaw('1 = 0');
        }

        // Guru can only see scores for subjects they teach
        $subjectIds = Schedule::where('teacher_id', $user->teacher->id)
            ->pluck('subject_id');

        return $query->whereIn('subject_id', $subjectIds);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeSkillScores::route('/'),
            'create' => CreateKnowledgeSkillScore::route('/create'),
            'edit' => EditKnowledgeSkillScore::route('/{record}/edit'),
        ];
    }
}
