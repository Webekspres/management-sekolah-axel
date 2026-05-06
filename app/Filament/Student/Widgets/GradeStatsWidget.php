<?php

namespace App\Filament\Student\Widgets;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Services\RaporService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class GradeStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $student = Auth::user()?->student;

        if ($student === null) {
            return [
                Stat::make('Mata Pelajaran', '0')->color('gray'),
                Stat::make('Rata-rata RAPOR', '0.00')->color('gray'),
                Stat::make('Di Bawah KKM', '0')->color('success'),
            ];
        }

        $academicYear = AcademicYear::where('is_active', true)->first();

        if ($academicYear === null) {
            return [
                Stat::make('Mata Pelajaran', '0')->color('gray'),
                Stat::make('Rata-rata RAPOR', '0.00')->color('gray'),
                Stat::make('Di Bawah KKM', '0')->color('success'),
            ];
        }

        $grades = Grade::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('grade_type', 'RAPOR')
            ->with('subject')
            ->get();

        $subjectCount = $grades->pluck('subject_id')->unique()->count();

        $avgRapor = $grades->isNotEmpty()
            ? $grades->avg('score')
            : 0.0;

        $belowKkmCount = $grades->filter(function (Grade $grade) use ($student): bool {
            $kkm = app(RaporService::class)->resolveKkm($student->schoolClass, $grade->subject_id);

            return (float) $grade->score < $kkm;
        })->count();

        return [
            Stat::make('Mata Pelajaran', number_format($subjectCount))
                ->description('Mata pelajaran dengan nilai RAPOR')
                ->color('info'),
            Stat::make('Rata-rata RAPOR', number_format((float) $avgRapor, 2))
                ->description('Rata-rata nilai RAPOR keseluruhan')
                ->color('success'),
            Stat::make('Di Bawah KKM', number_format($belowKkmCount))
                ->description('Mata pelajaran dengan RAPOR di bawah KKM')
                ->color($belowKkmCount > 0 ? 'danger' : 'success'),
        ];
    }
}
