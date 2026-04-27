<?php

namespace App\Filament\Clusters\Academic\Resources\SchoolClasses;

use App\Filament\Clusters\Academic\Resources\SchoolClasses\Pages\CreateSchoolClass;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Pages\ListSchoolClasses;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Schemas\SchoolClassForm;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Tables\SchoolClassesTable;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Kelas';

    protected static ?string $pluralLabel = 'Daftar Kelas';

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
            ->hasTemporaryPolicyGrant($user, 'viewAny', SchoolClass::class);
    }

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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolClasses::route('/'),
            'create' => CreateSchoolClass::route('/create'),
            'edit' => EditSchoolClass::route('/{record}/edit'),
        ];
    }
}
