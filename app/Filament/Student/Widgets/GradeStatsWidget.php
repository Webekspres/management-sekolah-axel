<?php

namespace App\Filament\Student\Widgets;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Services\RaporService;
use App\Support\DashboardAcademicContext;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class GradeStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 3,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'siswa_ortu';
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $student = $user?->student;

        if ($student === null) {
            return [
                Stat::make('Informasi', 'Akun belum terhubung ke data siswa')
                    ->color('gray'),
            ];
        }

        $academicYear = AcademicYear::where('is_active', true)->first();
        $ctx = DashboardAcademicContext::statsSuffix();

        if ($academicYear === null) {
            return [
                Stat::make('Mata Pelajaran', '0')
                    ->description('Tahun akademik aktif belum tersedia'.$ctx)
                    ->color('gray'),
                Stat::make('Rata-rata RAPOR', '0.00')
                    ->description('Tahun akademik aktif belum tersedia'.$ctx)
                    ->color('gray'),
                Stat::make('Di Bawah KKM', '0')
                    ->description('Tahun akademik aktif belum tersedia'.$ctx)
                    ->color('gray'),
            ];
        }

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('grade_type', 'RAPOR')
            ->get();

        $subjectCount = $grades->pluck('subject_id')->unique()->count();
        $avgRapor = $grades->isNotEmpty() ? (float) $grades->avg('score') : 0.0;

        $raporService = app(RaporService::class);
        $schoolClass = $student->schoolClass;

        $belowKkmCount = $grades->filter(function (Grade $grade) use ($raporService, $schoolClass): bool {
            $kkm = $raporService->resolveKkm($schoolClass, $grade->subject_id);

            return (float) $grade->score < $kkm;
        })->count();

        return [
            Stat::make('Mata Pelajaran', number_format($subjectCount))
                ->description('Mata pelajaran dengan nilai RAPOR'.$ctx)
                ->color('info'),
            Stat::make('Rata-rata RAPOR', number_format($avgRapor, 2))
                ->description('Rata-rata nilai RAPOR tahun akademik aktif'.$ctx)
                ->color('success'),
            Stat::make('Di Bawah KKM', number_format($belowKkmCount))
                ->description('Mata pelajaran dengan RAPOR di bawah KKM'.$ctx)
                ->color($belowKkmCount > 0 ? 'danger' : 'success'),
        ];
    }
}
