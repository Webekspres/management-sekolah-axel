<?php

namespace App\Filament\Clusters\Academic\Resources\SchoolClasses;

use App\Filament\Clusters\Academic\Resources\SchoolClasses\Pages\CreateSchoolClass;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Pages\ListSchoolClasses;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Schemas\SchoolClassForm;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Tables\SchoolClassesTable;
use App\Filament\Concerns\AuthorizesResourceAccessWithTemporaryGrant;
use App\Models\SchoolClass;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolClassResource extends Resource
{
    use AuthorizesResourceAccessWithTemporaryGrant;

    protected static ?string $model = SchoolClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Kelas';

    protected static ?string $pluralLabel = 'Daftar Kelas';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SchoolClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolClassesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolClasses::route('/'),
            'create' => CreateSchoolClass::route('/create'),
            'edit' => EditSchoolClass::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = auth()->user();

        $allowedLevelIds = app(TemporaryAccessManager::class)
            ->getAllowedLevelIds($user, SchoolClass::class);

        if ($allowedLevelIds !== null && $allowedLevelIds->isNotEmpty()) {
            $query->whereIn('level_id', $allowedLevelIds);
        }

        return $query;
    }
}
