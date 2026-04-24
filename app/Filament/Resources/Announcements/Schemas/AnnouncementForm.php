<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                RichEditor::make('content')
                    ->required()
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'min-height: 30vh;',
                    ]),
                CheckboxList::make('target_role')
                    ->options([
                        'super_admin' => 'Admin',
                        'kepala_sekolah' => 'Kepala sekolah',
                        'guru' => 'Guru',
                        'siswa_ortu' => 'Siswa dan Orang Tua',
                    ])
                    ->required(),
            ]);
    }
}
