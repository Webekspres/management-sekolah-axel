<?php

namespace App\Filament\Widgets;

use App\Filament\Clusters\Academic\Resources\Attendances\AttendanceResource;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\DashboardAcademicContext;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminOverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 1;

    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = 2;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $ctx = DashboardAcademicContext::statsSuffix();

        $attendanceTodayQuery = Attendance::query()
            ->whereHas('kbm', fn ($query) => $query->whereDate('date', today()));

        $attendanceTodayCount = (clone $attendanceTodayQuery)->count();
        $hadirCount = (clone $attendanceTodayQuery)->where('status', 'HADIR')->count();
        $izinCount = (clone $attendanceTodayQuery)->where('status', 'IZIN')->count();
        $sakitCount = (clone $attendanceTodayQuery)->where('status', 'SAKIT')->count();
        $alpaCount = (clone $attendanceTodayQuery)->where('status', 'ALPA')->count();

        return [
            $this->makeNavigableStat(
                label: 'Total Siswa',
                value: number_format(Student::query()->count()),
                description: 'Data siswa terdaftar'.$ctx,
                color: 'gray',
                url: StudentResource::getUrl(panel: 'admin'),
            ),
            $this->makeNavigableStat(
                label: 'Total Guru dan Staf',
                value: number_format(Teacher::query()->count()),
                description: 'Tenaga pendidik dan staf aktif'.$ctx,
                color: 'success',
                url: TeacherResource::getUrl(panel: 'admin'),
            ),
            $this->makeNavigableStat(
                label: 'Kelas Aktif',
                value: number_format(
                    SchoolClass::query()
                        ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
                        ->count()
                ),
                description: 'Kelas dengan tahun akademik aktif'.$ctx,
                color: 'primary',
                url: SchoolClassResource::getUrl(panel: 'admin'),
            ),
            $this->makeNavigableStat(
                label: 'Kehadiran Hari Ini',
                value: number_format($attendanceTodayCount),
                description: "Hadir {$hadirCount} · Izin {$izinCount} · Sakit {$sakitCount} · Alpa {$alpaCount}{$ctx}",
                color: 'info',
                url: AttendanceResource::getUrl(panel: 'admin'),
            ),
        ];
    }

    private function makeNavigableStat(
        string $label,
        string $value,
        string $description,
        string $color,
        ?string $url,
    ): Stat {
        $stat = Stat::make($label, $value)
            ->description($description)
            ->color($color);

        if ($url === null) {
            return $stat;
        }

        return $stat
            ->descriptionIcon('heroicon-m-arrow-right-circle')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'onclick' => "window.location.href = '{$url}'",
            ]);
    }
}
