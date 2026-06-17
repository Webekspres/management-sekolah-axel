<?php

namespace App\Filament\Clusters\Academic\Resources\Grades\Schemas;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('student_id')
                ->label('Siswa')
                ->options(fn () => Student::with('user')
                    ->get()
                    ->mapWithKeys(fn (Student $s) => [$s->id => $s->user?->name ?? $s->id])
                    ->all()
                )
                ->searchable()
                ->required(),

            Select::make('subject_id')
                ->label('Mata Pelajaran')
                ->options(fn () => Subject::orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required(),

            Select::make('academic_year_id')
                ->label('Tahun Akademik')
                ->options(fn () => AcademicYear::orderByDesc('name')->pluck('name', 'id')->all())
                ->default(fn () => AcademicYear::where('is_active', true)->value('id'))
                ->required(),

            Select::make('grade_type')
                ->label('Tipe Nilai')
                ->options(array_combine(Grade::GRADE_TYPES, Grade::GRADE_TYPES))
                ->required(),

            TextInput::make('score')
                ->label('Nilai (0–100)')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->step(0.01)
                ->required(),
        ]);
    }
}
