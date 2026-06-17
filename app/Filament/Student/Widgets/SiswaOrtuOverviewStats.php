<?php

namespace App\Filament\Student\Widgets;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Rapor;
use App\Services\RaporService;
use App\Support\DashboardAcademicContext;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SiswaOrtuOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Dashboard Siswa & Orang Tua';

    protected static ?int $sort = -2;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'lg' => 4,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'siswa_ortu';
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $student = $user?->student;

        if ($user === null || $student === null) {
            return [
                Stat::make('Tagihan Aktif', '0')
                    ->description('Akun belum terhubung ke data siswa')
                    ->color('gray'),
            ];
        }

        $ctx = DashboardAcademicContext::statsSuffix();

        $activeInvoiceCount = Invoice::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['UNPAID', 'PENDING'])
            ->count();

        $rapors = Rapor::where('student_id', $student->id)->get();
        $approvedRaporCount = $rapors->filter(fn (Rapor $rapor): bool => $rapor->isApproved())->count();

        $academicYear = AcademicYear::where('is_active', true)->first();
        $avgRapor = 0.0;
        $belowKkmCount = 0;

        if ($academicYear !== null) {
            $grades = Grade::where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('grade_type', 'RAPOR')
                ->with('subject')
                ->get();

            $avgRapor = $grades->isNotEmpty() ? (float) $grades->avg('score') : 0.0;

            $belowKkmCount = $grades->filter(function (Grade $grade) use ($student): bool {
                $kkm = app(RaporService::class)->resolveKkm($student->schoolClass, $grade->subject_id);

                return (float) $grade->score < $kkm;
            })->count();
        }

        return [
            Stat::make('Tagihan Aktif', number_format($activeInvoiceCount))
                ->description('Status UNPAID dan PENDING'.$ctx)
                ->color('warning'),
            Stat::make('Rapor Siap Diunduh', number_format($approvedRaporCount))
                ->description('Rapor dengan status APPROVED')
                ->color('success'),
            Stat::make('Rata-rata RAPOR', number_format($avgRapor, 2))
                ->description('Rata-rata nilai RAPOR keseluruhan'.$ctx)
                ->color('info'),
            Stat::make('Di Bawah KKM', number_format($belowKkmCount))
                ->description('Mata pelajaran dengan RAPOR di bawah KKM'.$ctx)
                ->color($belowKkmCount > 0 ? 'danger' : 'success'),
        ];
    }
}
