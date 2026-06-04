<?php

namespace App\Filament\Clusters\Academic\Resources\Rapors;

use App\Filament\Clusters\Academic\AcademicCluster;
use App\Filament\Clusters\Academic\Resources\Rapors\Pages\ListRapors;
use App\Filament\Clusters\Academic\Resources\Rapors\Tables\RaporsTable;
use App\Models\Rapor;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $cluster = AcademicCluster::class;

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $label = 'Rapor';

    protected static ?string $pluralLabel = 'Data Rapor';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Rapor::class) ?? false;
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
        return RaporsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student.user', 'academicYear']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRapors::route('/'),
        ];
    }
}
