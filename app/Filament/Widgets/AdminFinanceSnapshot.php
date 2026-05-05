<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminFinanceSnapshot extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = 1;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 7,
        'xl' => 7,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $openInvoices = Invoice::query()
            ->whereIn('status', ['UNPAID', 'PENDING'])
            ->count();

        $paymentsWeek = Payment::query()
            ->where('status', 'PAID')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('amount_paid');

        return [
            Stat::make('Tagihan terbuka', number_format($openInvoices))
                ->description('Status UNPAID atau PENDING')
                ->color('warning'),
            Stat::make('Pembayaran minggu ini', 'Rp '.number_format((float) $paymentsWeek, 0, ',', '.'))
                ->description('Payment PAID pada minggu berjalan')
                ->color('success'),
        ];
    }
}
