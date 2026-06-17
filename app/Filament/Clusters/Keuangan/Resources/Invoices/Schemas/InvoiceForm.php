<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices\Schemas;

use App\Enums\PaymentStatus;
use App\Filament\Forms\Components\MoneyInput;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\InvoiceService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('invoice_number')
                ->label('No. Tagihan')
                ->disabled()
                ->dehydrated()
                ->default(fn () => app(InvoiceService::class)->generateInvoiceNumber())
                ->visibleOn('create'),

            TextInput::make('status')
                ->label('Status')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn ($state): string => PaymentStatus::labelFor($state))
                ->visibleOn('edit'),

            Select::make('student_id')
                ->label('Siswa')
                ->options(fn (): array => self::studentOptions())
                ->searchable()
                ->required()
                ->live()
                ->disabled(fn (?Invoice $record): bool => $record?->isLockedForEditing() ?? false)
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    if ($state === null) {
                        return;
                    }

                    $student = Student::query()->with('schoolClass.level')->find($state);

                    if ($student !== null) {
                        $set('amount', app(InvoiceService::class)->resolveAmountForStudent($student));
                    }
                }),

            Select::make('academic_year_id')
                ->label('Tahun Akademik')
                ->options(fn () => AcademicYear::query()->orderByDesc('name')->pluck('name', 'id'))
                ->default(fn () => AcademicYear::query()->where('is_active', true)->value('id'))
                ->required()
                ->disabled(fn (?Invoice $record): bool => $record?->isLockedForEditing() ?? false),

            TextInput::make('description')
                ->label('Keterangan')
                ->required()
                ->maxLength(255)
                ->placeholder('Contoh: SPP Bulan Maret 2026'),

            TextInput::make('billing_period')
                ->label('Periode tagihan')
                ->placeholder('YYYY-MM')
                ->required()
                ->maxLength(7)
                ->disabled(fn (?Invoice $record): bool => $record?->isLockedForEditing() ?? false)
                ->dehydrated()
                ->visibleOn('create'),

            TextInput::make('billing_period')
                ->label('Periode tagihan')
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit'),

            MoneyInput::make('amount')
                ->label('Nominal')
                ->required()
                ->disabled(fn (?Invoice $record): bool => $record?->isLockedForEditing() ?? false),

            DatePicker::make('due_date')
                ->label('Jatuh tempo')
                ->required()
                ->native(false)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    if ($state === null) {
                        return;
                    }

                    $set('billing_period', Invoice::billingPeriodFromDate(Carbon::parse($state)));
                }),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function studentOptions(): array
    {
        $query = Student::query()->with(['user', 'schoolClass']);

        if ($levelId = session('active_academic_level_id')) {
            $query->whereHas(
                'schoolClass',
                fn (Builder $classQuery): Builder => $classQuery->where('level_id', $levelId),
            );
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => ($student->user?->name ?? $student->nipd).' — '.($student->schoolClass?->name ?? '-'),
            ])
            ->all();
    }
}
