<?php

namespace App\Filament\Student\Resources\Announcements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AnnouncementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Judul Pengumuman'),
                TextEntry::make('content')
                    ->label('Isi Pengumuman')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label('Diperbaharui Pada')
                    ->dateTime(),
            ]);
    }
}
