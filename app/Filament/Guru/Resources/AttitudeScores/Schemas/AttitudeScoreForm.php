<?php

namespace App\Filament\Guru\Resources\AttitudeScores\Schemas;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttitudeScoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('student_id')
                ->label('Siswa')
                ->options(function (): array {
                    /** @var User $user */
                    $user = auth()->user();

                    if (! $user?->teacher) {
                        return [];
                    }

                    $classIds = SchoolClass::where('teacher_id', $user->teacher->id)
                        ->pluck('id');

                    return Student::whereIn('class_id', $classIds)
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn (Student $s) => [$s->id => $s->user?->name ?? $s->id])
                        ->all();
                })
                ->searchable()
                ->required(),

            Select::make('academic_year_id')
                ->label('Tahun Akademik')
                ->options(fn () => AcademicYear::orderByDesc('name')->pluck('name', 'id')->all())
                ->default(fn () => AcademicYear::where('is_active', true)->value('id'))
                ->required(),

            Select::make('aspect')
                ->label('Aspek Penilaian')
                ->options([
                    'Spiritual' => 'Spiritual',
                    'Sosial' => 'Sosial',
                ])
                ->createOptionForm([
                    TextInput::make('aspect')
                        ->label('Nama Aspek Baru')
                        ->required(),
                ])
                ->createOptionUsing(fn (array $data): string => $data['aspect'])
                ->required(),

            TextInput::make('score')
                ->label('Nilai (0–100)')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->step(0.01)
                ->required(),

            Textarea::make('description')
                ->label('Deskripsi')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),
        ]);
    }
}
