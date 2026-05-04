<?php

namespace App\Filament\Student\Resources\Announcements;

use App\Filament\Student\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Student\Resources\Announcements\Pages\ViewAnnouncement;
use App\Filament\Student\Resources\Announcements\Schemas\AnnouncementInfolist;
use App\Filament\Student\Resources\Announcements\Tables\AnnouncementsTable;
use App\Models\Announcement;
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

    protected static ?string $recordTitleAttribute = 'Pengumuman';

    protected static UnitEnum|string|null $navigationGroup = 'Informasi Terkini';

    public static function infolist(Schema $schema): Schema
    {
        return AnnouncementInfolist::configure($schema);
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

        if ($role === 'super_admin') {
            return $query;
        }

        return $query->whereJsonContains('target_role', $role);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/pengumuman'),
            'view' => ViewAnnouncement::route('/{record}'),
        ];
    }
}
