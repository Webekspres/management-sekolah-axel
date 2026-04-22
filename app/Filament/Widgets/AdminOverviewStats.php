<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Dashboard Admin';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $activeUsers = User::query()
            ->where('is_active', true)
            ->count();

        $teacherCount = Teacher::query()->count();
        $studentCount = Student::query()->count();
        $classCount = SchoolClass::query()->count();
        $outstandingInvoiceCount = Invoice::query()
            ->whereIn('status', ['UNPAID', 'PENDING'])
            ->count();

        return [
            Stat::make('Pengguna Aktif', number_format($activeUsers))
                ->description('Akun aktif lintas role')
                ->color('success'),
            Stat::make('Total Guru', number_format($teacherCount))
                ->description('Guru terdaftar di sistem')
                ->color('info'),
            Stat::make('Total Siswa', number_format($studentCount))
                ->description('Akun siswa dan orang tua terpadu')
                ->color('info'),
            Stat::make('Total Kelas', number_format($classCount))
                ->description('Kelas aktif pada tahun berjalan')
                ->color('warning'),
            Stat::make('Tagihan Belum Lunas', number_format($outstandingInvoiceCount))
                ->description('Status UNPAID dan PENDING')
                ->color('danger'),
        ];
    }
}
