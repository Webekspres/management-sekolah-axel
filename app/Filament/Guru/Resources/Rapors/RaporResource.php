<?php

namespace App\Filament\Guru\Resources\Rapors;

use App\Filament\Guru\Resources\Rapors\Pages\ListRapors;
use App\Filament\Guru\Resources\Rapors\Tables\RaporsTable;
use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RaporResource extends Resource
{
    protected static ?string $model = Rapor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Penilaian';

    protected static ?string $label = 'Rapor Siswa';

    protected static ?string $pluralLabel = 'Rapor Siswa';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->role === 'guru';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return RaporsTable::configureForGuru($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student.user', 'academicYear']);

        /** @var User $user */
        $user = auth()->user();

        if (! $user?->teacher) {
            return $query->whereRaw('1 = 0');
        }

        // Wali Kelas: only see rapors for students in their class
        $classIds = SchoolClass::where('teacher_id', $user->teacher->id)->pluck('id');

        return $query->whereHas(
            'student',
            fn (Builder $q) => $q->whereIn('class_id', $classIds),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRapors::route('/'),
        ];
    }
}
