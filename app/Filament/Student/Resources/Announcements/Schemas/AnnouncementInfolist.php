<?php

namespace App\Filament\Student\Resources\Announcements\Schemas;

use App\Models\Announcement;
use App\Support\AnnouncementRichContentPreview;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\HtmlString;

class AnnouncementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('')
                            ->formatStateUsing(fn ($state, Announcement $record): string => $record->created_at?->translatedFormat('d F Y, H:i') ?? '-')
                            ->color('gray')
                            ->size(TextSize::Small)
                            ->columnSpanFull(),
                        TextEntry::make('content')
                            ->label('')
                            ->formatStateUsing(fn (?string $state): HtmlString => new HtmlString(
                                AnnouncementRichContentPreview::make($state)->toHtml(),
                            ))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'max-w-3xl',
                    ]),
            ]);
    }
}
