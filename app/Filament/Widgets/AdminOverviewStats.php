<?php

namespace App\Filament\Widgets;

use App\Filament\Clusters\Academic\Resources\Schedules\ScheduleResource;
use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\Payment;
use App\Models\Rapor;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminOverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Sistem Informasi Sekolah';

    protected ?string $description = 'Arsip';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        return [
            $this->makeNavigableStat(
                label: 'Data Siswa',
                value: number_format(Student::query()->count()),
                description: 'Lihat Detail',
                color: 'gray',
                url: StudentResource::getUrl(panel: 'admin'),
            ),
            $this->makeNavigableStat(
                label: 'Data Guru dan Staf',
                value: number_format(Teacher::query()->count()),
                description: 'Lihat Detail',
                color: 'success',
                url: TeacherResource::getUrl(panel: 'admin'),
            ),
            $this->makeNavigableStat(
                label: 'RPP',
                value: number_format(LessonPlan::query()->count()),
                description: 'Menu belum tersedia',
                color: 'primary',
                url: null,
            ),
            $this->makeNavigableStat(
                label: 'Rapor Siswa',
                value: number_format(Rapor::query()->count()),
                description: 'Menu belum tersedia',
                color: 'warning',
                url: null,
            ),
            $this->makeNavigableStat(
                label: 'Absen Kehadiran',
                value: number_format(Attendance::query()->count()),
                description: 'Menu belum tersedia',
                color: 'info',
                url: null,
            ),
            $this->makeNavigableStat(
                label: 'Laporan KBM',
                value: number_format(Kbm::query()->count()),
                description: 'Menu belum tersedia',
                color: 'danger',
                url: null,
            ),
            $this->makeNavigableStat(
                label: 'SPP Siswa',
                value: number_format(Invoice::query()->count()),
                description: 'Menu belum tersedia',
                color: 'gray',
                url: null,
            ),
            $this->makeNavigableStat(
                label: 'Ulangan dan Tugas',
                value: number_format(Grade::query()->count()),
                description: 'Menu belum tersedia',
                color: 'danger',
                url: null,
            ),
            $this->makeNavigableStat(
                label: 'Jadwal Mengajar',
                value: number_format(Schedule::query()->count()),
                description: 'Lihat Detail',
                color: 'primary',
                url: ScheduleResource::getUrl(panel: 'admin'),
            ),
            $this->makeNavigableStat(
                label: 'Lembur',
                value: 'Belum tersedia',
                description: 'Menu belum tersedia',
                color: 'success',
                url: null,
            ),
            $this->makeNavigableStat(
                label: 'Pengeluaran',
                value: number_format(Payment::query()->count()),
                description: 'Menu belum tersedia',
                color: 'warning',
                url: null,
            ),
            $this->makeNavigableStat(
                label: 'Data Kehadiran Siswa',
                value: number_format(Attendance::query()->count()),
                description: 'Menu belum tersedia',
                color: 'gray',
                url: null,
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
