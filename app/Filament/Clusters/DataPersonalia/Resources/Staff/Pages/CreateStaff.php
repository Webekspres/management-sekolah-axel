<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Staff\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Staff\StaffResource;
use App\Models\Address;
use App\Models\City;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $birthCity = filled($data['place_of_birth'] ?? null)
            ? City::query()->where('name', $data['place_of_birth'])->first()
            : null;

        $addressData = array_filter([
            'province_id' => $data['address_province_id'] ?? null,
            'city_id' => $data['address_city_id'] ?? null,
            'sub_district_id' => $data['address_sub_district_id'] ?? null,
            'village_id' => $data['address_village_id'] ?? null,
            'street' => $data['address_street'] ?? null,
            'postal_code' => $data['address_postal_code'] ?? null,
        ], fn ($v) => $v !== null);

        unset(
            $data['address_province_id'],
            $data['address_city_id'],
            $data['address_sub_district_id'],
            $data['address_village_id'],
            $data['address_street'],
            $data['address_postal_code'],
            $data['birth_province_id'],
        );

        return DB::transaction(function () use ($addressData, $birthCity, $data): Model {
            $data['city_id'] = $birthCity?->id;
            $data['email_verified_at'] = now();

            if (! empty($addressData)) {
                $address = Address::query()->create($addressData);
                $data['address_id'] = $address->id;
                $data['city_id'] = $addressData['city_id'] ?? $data['city_id'];
            }

            return static::getModel()::create($data);
        });
    }
}
