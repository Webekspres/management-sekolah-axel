<?php

namespace App\Filament\Student\Resources\Announcements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengumuman')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul')
                            ->columnSpanFull(),
                        TextEntry::make('content')
                            ->label('Isi Pengumuman')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Section::make('Informasi')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime(),
                    ]),
            ]);
    }
}
