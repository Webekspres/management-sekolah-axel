<?php

namespace App\Filament\Guru\Resources\PersonalityScores;

use App\Filament\Guru\Resources\PersonalityScores\Pages\CreatePersonalityScore;
use App\Filament\Guru\Resources\PersonalityScores\Pages\EditPersonalityScore;
use App\Filament\Guru\Resources\PersonalityScores\Pages\ListPersonalityScores;
use App\Filament\Guru\Resources\PersonalityScores\Schemas\PersonalityScoreForm;
use App\Filament\Guru\Resources\PersonalityScores\Tables\PersonalityScoresTable;
use App\Models\PersonalityScore;
use App\Models\SchoolClass;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PersonalityScoreResource extends Resource
{
    protected static ?string $model = PersonalityScore::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static UnitEnum|string|null $navigationGroup = 'Penilaian';

    protected static ?string $label = 'Kepribadian Siswa';

    protected static ?string $pluralLabel = 'Kepribadian Siswa';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->role === 'guru';
    }

    public static function form(Schema $schema): Schema
    {
        return PersonalityScoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonalityScoresTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student.user', 'academicYear']);

        /** @var User $user */
        $user = auth()->user();

        if (! $user?->teacher) {
            return $query->whereRaw('1 = 0');
        }

        // Wali Kelas: only see personality scores for students in their class
        $classIds = SchoolClass::where('teacher_id', $user->teacher->id)->pluck('id');

        return $query->whereHas(
            'student',
            fn (Builder $q) => $q->whereIn('class_id', $classIds),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonalityScores::route('/'),
            'create' => CreatePersonalityScore::route('/create'),
            'edit' => EditPersonalityScore::route('/{record}/edit'),
        ];
    }
}
