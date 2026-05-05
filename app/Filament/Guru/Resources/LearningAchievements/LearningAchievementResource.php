<?php

namespace App\Filament\Guru\Resources\LearningAchievements;

use App\Filament\Guru\Resources\LearningAchievements\Pages\CreateLearningAchievement;
use App\Filament\Guru\Resources\LearningAchievements\Pages\EditLearningAchievement;
use App\Filament\Guru\Resources\LearningAchievements\Pages\ListLearningAchievements;
use App\Filament\Guru\Resources\LearningAchievements\Schemas\LearningAchievementForm;
use App\Filament\Guru\Resources\LearningAchievements\Tables\LearningAchievementsTable;
use App\Models\LearningAchievement;
use App\Models\Schedule;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LearningAchievementResource extends Resource
{
    protected static ?string $model = LearningAchievement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Penilaian';

    protected static ?string $label = 'Capaian Pembelajaran';

    protected static ?string $pluralLabel = 'Capaian Pembelajaran';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->role === 'guru';
    }

    public static function form(Schema $schema): Schema
    {
        return LearningAchievementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningAchievementsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student.user', 'subject', 'academicYear']);

        /** @var User $user */
        $user = auth()->user();

        if (! $user?->teacher) {
            return $query->whereRaw('1 = 0');
        }

        $subjectIds = Schedule::where('teacher_id', $user->teacher->id)->pluck('subject_id');

        return $query->whereIn('subject_id', $subjectIds);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLearningAchievements::route('/'),
            'create' => CreateLearningAchievement::route('/create'),
            'edit' => EditLearningAchievement::route('/{record}/edit'),
        ];
    }
}
