<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\Announcements\Schemas\AnnouncementForm;
use App\Filament\Resources\Announcements\Tables\AnnouncementsTable;
use App\Models\Announcement;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSpeakerWave;

    protected static UnitEnum|string|null $navigationGroup = 'Informasi Terkini';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $label = 'Pengumuman';

    protected static ?string $pluralLabel = 'Pengumuman';

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();
        $role = $user->effectiveRole();

        // super_admin, kepala_sekolah, dan guru dapat melihat semua pengumuman
        // karena mereka adalah pembuat pengumuman dan perlu melihat semua yang ada
        if (in_array($role, ['super_admin', 'kepala_sekolah', 'guru'], true)) {
            return $query;
        }

        if (app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, 'viewAny', Announcement::class)) {
            return $query;
        }

        return $query->whereJsonContains('target_role', $role);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
