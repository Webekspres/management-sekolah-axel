<?php

namespace App\Filament\Kepsek\Resources\LessonPlans\Tables;

use App\Models\LessonPlan;
use App\Support\PublicStorageUrl;
use App\Support\RichText;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['subjectForDisplay', 'teacher.user']))
            ->columns([
                TextColumn::make('teacher.user.name')
                    ->label('Guru')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subjectForDisplay.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('topic')
                    ->label('Topik')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'PENDING' => 'warning',
                        'REVISED' => 'danger',
                        'APPROVED' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('revision_note')
                    ->label('Catatan Revisi')
                    ->formatStateUsing(fn (?string $state): string => RichText::display($state))
                    ->toggleable()
                    ->limit(60),
                TextColumn::make('file_path')
                    ->label('File')
                    ->formatStateUsing(fn (string $state): string => basename($state))
                    ->url(fn (LessonPlan $record): string => PublicStorageUrl::fromPublicDiskPath($record->file_path), shouldOpenInNewTab: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PENDING' => 'Pending',
                        'REVISED' => 'Revisi',
                        'APPROVED' => 'Approved',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah Status'),
            ]);
    }
}
