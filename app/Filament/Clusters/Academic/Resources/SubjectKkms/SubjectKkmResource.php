<?php

namespace App\Filament\Clusters\Academic\Resources\SubjectKkms;

use App\Filament\Clusters\Academic\AcademicCluster;
use App\Filament\Clusters\Academic\Resources\SubjectKkms\Pages\CreateSubjectKkm;
use App\Filament\Clusters\Academic\Resources\SubjectKkms\Pages\EditSubjectKkm;
use App\Filament\Clusters\Academic\Resources\SubjectKkms\Pages\ListSubjectKkms;
use App\Filament\Clusters\Academic\Resources\SubjectKkms\Schemas\SubjectKkmForm;
use App\Filament\Clusters\Academic\Resources\SubjectKkms\Tables\SubjectKkmsTable;
use App\Models\SubjectKkm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SubjectKkmResource extends Resource
{
    protected static ?string $model = SubjectKkm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $cluster = AcademicCluster::class;

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $label = 'KKM Mata Pelajaran';

    protected static ?string $pluralLabel = 'KKM Mata Pelajaran';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', SubjectKkm::class) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return SubjectKkmForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubjectKkmsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubjectKkms::route('/'),
            'create' => CreateSubjectKkm::route('/create'),
            'edit' => EditSubjectKkm::route('/{record}/edit'),
        ];
    }
}
