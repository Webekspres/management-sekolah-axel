<?php

namespace App\Filament\Guru\Resources\KnowledgeSkillScores\Tables;

use App\Models\KnowledgeSkillScore;
use App\Models\SubjectKkm;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KnowledgeSkillScoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['student.user', 'subject', 'academicYear']))
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Tahun Akademik')
                    ->sortable(),
                TextColumn::make('kkm')
                    ->label('KKM')
                    ->state(function (KnowledgeSkillScore $record): string {
                        $levelId = $record->student?->schoolClass?->level_id;
                        if (! $levelId) {
                            return '70.00';
                        }

                        return number_format(SubjectKkm::getKkm($record->subject_id, $levelId), 2);
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('knowledge_score')
                    ->label('Nilai Pengetahuan')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(function (KnowledgeSkillScore $record): string {
                        $levelId = $record->student?->schoolClass?->level_id;
                        $kkm = $levelId ? SubjectKkm::getKkm($record->subject_id, $levelId) : 70.0;

                        return $record->knowledge_score !== null && (float) $record->knowledge_score < $kkm
                            ? 'danger'
                            : 'success';
                    }),
                TextColumn::make('knowledge_predicate')
                    ->label('Predikat P')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('skill_score')
                    ->label('Nilai Keterampilan')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(function (KnowledgeSkillScore $record): string {
                        $levelId = $record->student?->schoolClass?->level_id;
                        $kkm = $levelId ? SubjectKkm::getKkm($record->subject_id, $levelId) : 70.0;

                        return $record->skill_score !== null && (float) $record->skill_score < $kkm
                            ? 'danger'
                            : 'success';
                    }),
                TextColumn::make('skill_predicate')
                    ->label('Predikat K')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('student.user.name', 'asc')
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
