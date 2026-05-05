<?php

namespace App\Filament\Guru\Resources\AttitudeScores;

use App\Filament\Guru\Resources\AttitudeScores\Pages\CreateAttitudeScore;
use App\Filament\Guru\Resources\AttitudeScores\Pages\EditAttitudeScore;
use App\Filament\Guru\Resources\AttitudeScores\Pages\ListAttitudeScores;
use App\Filament\Guru\Resources\AttitudeScores\Schemas\AttitudeScoreForm;
use App\Filament\Guru\Resources\AttitudeScores\Tables\AttitudeScoresTable;
use App\Models\AttitudeScore;
use App\Models\SchoolClass;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AttitudeScoreResource extends Resource
{
    protected static ?string $model = AttitudeScore::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFaceSmile;

    protected static UnitEnum|string|null $navigationGroup = 'Penilaian';

    protected static ?string $label = 'Nilai Sikap';

    protected static ?string $pluralLabel = 'Nilai Sikap';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->role === 'guru';
    }

    public static function form(Schema $schema): Schema
    {
        return AttitudeScoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttitudeScoresTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student.user', 'academicYear']);

        /** @var User $user */
        $user = auth()->user();

        if (! $user?->teacher) {
            return $query->whereRaw('1 = 0');
        }

        // Wali Kelas: only see attitude scores for students in their class
        $classIds = SchoolClass::where('teacher_id', $user->teacher->id)
            ->pluck('id');

        return $query->whereHas(
            'student',
            fn (Builder $q) => $q->whereIn('class_id', $classIds),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttitudeScores::route('/'),
            'create' => CreateAttitudeScore::route('/create'),
            'edit' => EditAttitudeScore::route('/{record}/edit'),
        ];
    }
}
