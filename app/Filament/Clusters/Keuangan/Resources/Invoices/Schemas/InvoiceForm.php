<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices\Schemas;

use App\Enums\PaymentStatus;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\InvoiceService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

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

            Select::make('student_id')
                ->label('Siswa')
                ->options(fn () => Student::query()
                    ->with('user')
                    ->get()
                    ->mapWithKeys(fn (Student $student) => [
                        $student->id => ($student->user?->name ?? $student->nipd).' — '.($student->schoolClass?->name ?? '-'),
                    ])
                    ->all())
                ->searchable()
                ->required()
                ->live()
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
                ->required(),

            TextInput::make('description')
                ->label('Keterangan')
                ->required()
                ->maxLength(255)
                ->placeholder('Contoh: SPP Bulan Maret 2026'),

            TextInput::make('amount')
                ->label('Nominal (Rp)')
                ->numeric()
                ->required()
                ->minValue(0),

            DatePicker::make('due_date')
                ->label('Jatuh tempo')
                ->required()
                ->native(false),

            Select::make('status')
                ->label('Status')
                ->options(PaymentStatus::options())
                ->default(PaymentStatus::Unpaid->value)
                ->required()
                ->visibleOn('edit'),
        ]);
    }
}
