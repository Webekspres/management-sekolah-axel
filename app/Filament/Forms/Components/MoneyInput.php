<?php

namespace App\Filament\Forms\Components;

use App\Support\MonetaryAmount;
use App\Support\MoneyFormat;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\NumberStateCast;
use Filament\Support\RawJs;

class MoneyInput extends TextInput
{
    public static function make(?string $name = null): static
    {
        $field = parent::make($name);

        return $field->configureAsMoneyInput();
    }

    public function configureAsMoneyInput(): static
    {
        return $this
            ->prefix('Rp')
            ->inputMode('decimal')
            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
            ->stripCharacters('.')
            ->formatStateUsing(fn (mixed $state): ?string => MoneyFormat::formatForInput($state))
            ->dehydrateStateUsing(fn (mixed $state): ?float => MoneyFormat::parse($state))
            ->rule('numeric')
            ->rule('min:0')
            ->rule('max:'.MonetaryAmount::MAX);
    }

    /**
     * Avoid NumberStateCast: floatval() breaks Indonesian thousands (e.g. "1.500.000" → 1.5).
     *
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        $casts = parent::getDefaultStateCasts();

        return array_values(array_filter(
            $casts,
            fn (mixed $cast): bool => ! $cast instanceof NumberStateCast,
        ));
    }
}
