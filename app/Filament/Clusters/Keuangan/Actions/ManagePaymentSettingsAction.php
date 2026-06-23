<?php

namespace App\Filament\Clusters\Keuangan\Actions;

use App\Enums\UserRole;
use App\Services\SchoolPaymentSettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class ManagePaymentSettingsAction
{
    public static function make(): Action
    {
        return Action::make('manage_payment_settings')
            ->authorize(fn (): bool => auth()->user()?->hasUserRole(UserRole::SuperAdmin) ?? false)
            ->label(__('pembayaran.payment_settings.action_label'))
            ->icon(Heroicon::OutlinedBuildingLibrary)
            ->iconButton()
            ->tooltip(__('pembayaran.payment_settings.action_label'))
            ->color('gray')
            ->slideOver()
            ->modalHeading(__('pembayaran.payment_settings.heading'))
            ->modalDescription(__('pembayaran.payment_settings.description'))
            ->modalSubmitActionLabel(__('pembayaran.payment_settings.submit'))
            ->modalCancelActionLabel(__('pembayaran.payment_settings.cancel'))
            ->schema(self::schema())
            ->fillForm(fn (): array => app(SchoolPaymentSettingsService::class)->formDefaults())
            ->action(function (array $data): void {
                app(SchoolPaymentSettingsService::class)->updateFromFormData($data);

                Notification::make()
                    ->title(__('pembayaran.payment_settings.saved'))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, Section>
     */
    protected static function schema(): array
    {
        return [
            Section::make(__('pembayaran.payment_settings.section'))
                ->description(__('pembayaran.payment_settings.section_hint'))
                ->schema([
                    TextInput::make('bank_name')
                        ->label(__('pembayaran.payment_settings.bank_name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('account_number')
                        ->label(__('pembayaran.payment_settings.account_number'))
                        ->required()
                        ->numeric()
                        ->inputMode('numeric')
                        ->regex('/^\d+$/')
                        ->maxLength(50),
                    TextInput::make('account_holder')
                        ->label(__('pembayaran.payment_settings.account_holder'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('school_whatsapp')
                        ->label(__('pembayaran.payment_settings.school_whatsapp'))
                        ->required()
                        ->tel()
                        ->maxLength(20),
                ]),
        ];
    }
}
