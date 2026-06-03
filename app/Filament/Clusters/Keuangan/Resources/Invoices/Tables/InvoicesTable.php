<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices\Tables;

use App\Enums\PaymentStatus;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Services\InvoiceService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.user.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.schoolClass.name')
                    ->label('Kelas'),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => PaymentStatus::labelFor($state))
                    ->color(fn ($state): string => PaymentStatus::colorFor($state)),
                TextColumn::make('due_date')
                    ->label('Jatuh tempo')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PaymentStatus::options()),
                SelectFilter::make('academic_year_id')
                    ->label('Tahun Akademik')
                    ->relationship('academicYear', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('generate_spp_class')
                        ->label('Generate tagihan per kelas')
                        ->icon('heroicon-o-document-plus')
                        ->form([
                            Select::make('school_class_id')
                                ->label('Kelas')
                                ->options(fn () => SchoolClass::query()
                                    ->with('academicYear')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (SchoolClass $class) => [
                                        $class->id => $class->name.' ('.($class->academicYear?->name ?? '-').')',
                                    ]))
                                ->searchable()
                                ->required(),
                            TextInput::make('description')
                                ->label('Keterangan tagihan')
                                ->required()
                                ->default(fn () => 'SPP '.now()->translatedFormat('F Y')),
                            DatePicker::make('due_date')
                                ->label('Jatuh tempo')
                                ->required()
                                ->default(now()->addDays(14)),
                        ])
                        ->action(function (array $data): void {
                            $class = SchoolClass::query()->findOrFail($data['school_class_id']);
                            $academicYear = $class->academicYear ?? AcademicYear::query()->where('is_active', true)->first();

                            if ($academicYear === null) {
                                Notification::make()->title('Tahun akademik tidak ditemukan.')->danger()->send();

                                return;
                            }

                            $count = app(InvoiceService::class)->bulkGenerateForSchoolClass(
                                $class,
                                $academicYear,
                                $data['description'],
                                Carbon::parse($data['due_date']),
                            );

                            Notification::make()
                                ->title("{$count} tagihan berhasil dibuat.")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
