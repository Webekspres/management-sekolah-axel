<?php

namespace App\Filament\Student\Resources\LessonPlanMaterials\Tables;

use App\Models\LessonPlanMaterial;
use App\Support\PublicStorageUrl;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonPlanMaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject_name')
                    ->label('Mata Pelajaran')
                    ->state(fn (LessonPlanMaterial $record): ?string => $record->lessonPlan?->subjectForDisplay?->name),
                TextColumn::make('topic')
                    ->label('Topik')
                    ->state(fn (LessonPlanMaterial $record): ?string => $record->lessonPlan?->topic),
                TextColumn::make('class_name')
                    ->label('Kelas')
                    ->state(fn (LessonPlanMaterial $record): ?string => $record->lessonPlan?->schoolClass?->name),
                TextColumn::make('original_filename')
                    ->label('Nama File'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Unduh')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (LessonPlanMaterial $record): string => PublicStorageUrl::fromPublicDiskPath($record->file_path))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Belum ada materi pembelajaran')
            ->emptyStateDescription('Materi pembelajaran akan muncul di sini setelah RPP disetujui oleh kepala sekolah.');
    }
}
