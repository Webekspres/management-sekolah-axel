<?php

namespace App\Filament\Forms;

use App\Models\City;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Reusable cascading location select fields.
 *
 * Usage:
 *   LocationSelect::make('province_id', 'city_id', 'sub_district_id', 'village_id')
 *
 * Generates 4 cascading Select fields:
 *   Provinsi → Kota/Kabupaten → Kecamatan → Desa/Kelurahan
 *
 * @return array<Select>
 */
class LocationSelect
{
    /**
     * @return array<Select>
     */
    public static function make(
        string $provinceField = 'province_id',
        string $cityField = 'city_id',
        string $subDistrictField = 'sub_district_id',
        string $villageField = 'village_id',
    ): array {
        return [
            Select::make($provinceField)
                ->label('Provinsi')
                ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Set $set) use ($cityField, $subDistrictField, $villageField): void {
                    $set($cityField, null);
                    $set($subDistrictField, null);
                    $set($villageField, null);
                }),

            Select::make($cityField)
                ->label('Kota/Kabupaten')
                ->options(function (Get $get) use ($provinceField): array {
                    $provinceId = $get($provinceField);

                    if (! $provinceId) {
                        return [];
                    }

                    return City::query()
                        ->where('province_id', $provinceId)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->live()
                ->afterStateUpdated(function (Set $set) use ($subDistrictField, $villageField): void {
                    $set($subDistrictField, null);
                    $set($villageField, null);
                }),

            Select::make($subDistrictField)
                ->label('Kecamatan')
                ->options(function (Get $get) use ($cityField): array {
                    $cityId = $get($cityField);

                    if (! $cityId) {
                        return [];
                    }

                    return SubDistrict::query()
                        ->where('city_id', $cityId)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->live()
                ->afterStateUpdated(function (Set $set) use ($villageField): void {
                    $set($villageField, null);
                }),

            Select::make($villageField)
                ->label('Desa/Kelurahan')
                ->options(function (Get $get) use ($subDistrictField): array {
                    $subDistrictId = $get($subDistrictField);

                    if (! $subDistrictId) {
                        return [];
                    }

                    return Village::query()
                        ->where('sub_district_id', $subDistrictId)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable(),
        ];
    }
}
