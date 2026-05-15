<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Staff;

use App\Filament\Clusters\DataPersonalia\Resources\Staff\Pages\CreateStaff;
use App\Filament\Clusters\DataPersonalia\Resources\Staff\Pages\EditStaff;
use App\Filament\Clusters\DataPersonalia\Resources\Staff\Pages\ListStaff;
use App\Filament\Clusters\DataPersonalia\Resources\Staff\Schemas\StaffForm;
use App\Filament\Clusters\DataPersonalia\Resources\Staff\Tables\StaffTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = null;

    protected static UnitEnum|string|null $navigationGroup = 'Data Personalia';

    protected static ?string $label = 'Admin & Kepsek';

    protected static ?string $pluralLabel = 'Admin & Kepala Sekolah';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = 'data-personalia/staff';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('role', ['super_admin', 'kepala_sekolah']);
    }

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
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
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
