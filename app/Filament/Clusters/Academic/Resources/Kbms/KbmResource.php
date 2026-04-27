<?php

namespace App\Filament\Clusters\Academic\Resources\Kbms;

use App\Filament\Clusters\Academic\AcademicCluster;
use App\Filament\Clusters\Academic\Resources\Kbms\Pages\CreateKbm;
use App\Filament\Clusters\Academic\Resources\Kbms\Pages\EditKbm;
use App\Filament\Clusters\Academic\Resources\Kbms\Pages\ListKbms;
use App\Filament\Clusters\Academic\Resources\Kbms\Schemas\KbmForm;
use App\Filament\Clusters\Academic\Resources\Kbms\Tables\KbmsTable;
use App\Models\Kbm;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KbmResource extends Resource
{
    protected static ?string $model = Kbm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $cluster = AcademicCluster::class;

    protected static ?string $label = 'Laporan KBM';

    protected static ?string $pluralLabel = 'Laporan KBM';

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
            ->hasTemporaryPolicyGrant($user, 'viewAny', Kbm::class);
    }

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
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'schedule.teacher.user',
                'schedule.schoolClass',
                'schedule.subjectForDisplay',
                'lessonPlan.subjectForDisplay',
            ]);
    }
}
