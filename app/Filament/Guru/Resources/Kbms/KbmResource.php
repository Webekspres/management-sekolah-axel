<?php

namespace App\Filament\Guru\Resources\Kbms;

use App\Filament\Guru\Resources\Kbms\Pages\CreateKbm;
use App\Filament\Guru\Resources\Kbms\Pages\EditKbm;
use App\Filament\Guru\Resources\Kbms\Pages\InputKbmAttendance;
use App\Filament\Guru\Resources\Kbms\Pages\ListKbms;
use App\Filament\Guru\Resources\Kbms\Schemas\KbmForm;
use App\Filament\Guru\Resources\Kbms\Tables\KbmsTable;
use App\Models\Kbm;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class KbmResource extends Resource
{
    protected static ?string $model = Kbm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $cluster = null;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Laporan KBM';

    protected static ?string $pluralLabel = 'Laporan KBM';

    public static function form(Schema $schema): Schema
    {
        return KbmForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KbmsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKbms::route('/'),
            'create' => CreateKbm::route('/create'),
            'edit' => EditKbm::route('/{record}/edit'),
            'attendance' => InputKbmAttendance::route('/{record}/attendance'),
        ];
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === 'guru') {
            return true;
        }

        return app(TemporaryAccessManager::class)
            ->hasTemporaryPolicyGrant($user, 'viewAny', Kbm::class);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = auth()->user();

        if (app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, 'viewAny', Kbm::class)) {
            return $query;
        }

        if (! $user->teacher) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('schedule', function (Builder $builder) use ($user): void {
            $builder->where('teacher_id', $user->teacher->id);
        });
    }
}
