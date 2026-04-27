<?php

namespace App\Filament\Clusters\Academic\Resources\Subjects;

use App\Filament\Clusters\Academic\Resources\Subjects\Pages\CreateSubject;
use App\Filament\Clusters\Academic\Resources\Subjects\Pages\EditSubject;
use App\Filament\Clusters\Academic\Resources\Subjects\Pages\ListSubjects;
use App\Filament\Clusters\Academic\Resources\Subjects\Schemas\SubjectForm;
use App\Filament\Clusters\Academic\Resources\Subjects\Tables\SubjectsTable;
use App\Models\Subject;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $cluster = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Mata Pelajaran';

    protected static ?string $pluralLabel = 'Daftar Mata Pelajaran';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        return app(TemporaryAccessManager::class)
            ->hasTemporaryPolicyGrant($user, 'viewAny', Subject::class);
    }

    public static function form(Schema $schema): Schema
    {
        return SubjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubjects::route('/'),
            'create' => CreateSubject::route('/create'),
            'edit' => EditSubject::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = auth()->user();

        $allowedLevelIds = app(TemporaryAccessManager::class)
            ->getAllowedLevelIds($user, Subject::class);

        if ($allowedLevelIds !== null && $allowedLevelIds->isNotEmpty()) {
            $query->whereIn('level_id', $allowedLevelIds);
        }

        return $query;
    }
}
