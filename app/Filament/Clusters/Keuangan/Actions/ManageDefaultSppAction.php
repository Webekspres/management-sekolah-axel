<?php

namespace App\Filament\Clusters\Keuangan\Actions;

use App\Enums\UserRole;
use App\Filament\Forms\Components\MoneyInput;
use App\Models\Level;
use App\Services\LevelDefaultSppService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class ManageDefaultSppAction
{
    public static function make(): Action
    {
        return Action::make('manage_default_spp')
            ->authorize(fn (): bool => auth()->user()?->hasUserRole(UserRole::SuperAdmin) ?? false)
            ->label(__('pembayaran.default_spp.action_label'))
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->iconButton()
            ->tooltip(__('pembayaran.default_spp.action_label'))
            ->color('gray')
            ->slideOver()
            ->modalHeading(__('pembayaran.default_spp.heading'))
            ->modalDescription(__('pembayaran.default_spp.description'))
            ->modalSubmitActionLabel(__('pembayaran.default_spp.submit'))
            ->modalCancelActionLabel(__('pembayaran.default_spp.cancel'))
            ->schema(fn (): array => self::schema())
            ->fillForm(fn (): array => app(LevelDefaultSppService::class)->formDefaults())
            ->action(function (array $data): void {
                app(LevelDefaultSppService::class)->updateFromFormData($data['levels'] ?? []);

                Notification::make()
                    ->title(__('pembayaran.default_spp.saved'))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, Section>
     */
    protected static function schema(): array
    {
        $service = app(LevelDefaultSppService::class);
        $levels = $service->levelsOrderedByName();

        if ($levels->isEmpty()) {
            return [];
        }

        $fields = $levels->map(
            fn (Level $level): MoneyInput => MoneyInput::make("levels.{$level->id}")
                ->label($level->name)
                ->required(),
        )->all();

        return [
            Section::make(__('pembayaran.default_spp.section'))
                ->description(__('pembayaran.default_spp.section_hint'))
                ->schema($fields),
        ];
    }
}
