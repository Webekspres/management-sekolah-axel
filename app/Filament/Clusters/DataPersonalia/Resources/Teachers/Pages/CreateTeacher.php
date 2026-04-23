<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
use App\Models\Address;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $userData = $data['user'] ?? [];

        $userPayload = [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'gender' => $userData['gender'] ?? 'L',
            'phone_number' => $userData['phone_number'] ?? null,
            'date_of_birth' => $userData['date_of_birth'] ?? null,
            'place_of_birth' => $userData['place_of_birth'] ?? null,
            'address_detail' => $userData['address_detail'] ?? null,
            'role' => 'guru',
        ];

        // Handle address
        $addressData = array_filter([
            'province_id' => $data['address_province_id'] ?? null,
            'city_id' => $data['address_city_id'] ?? null,
            'sub_district_id' => $data['address_sub_district_id'] ?? null,
            'village_id' => $data['address_village_id'] ?? null,
            'street' => $data['address_street'] ?? null,
            'postal_code' => $data['address_postal_code'] ?? null,
        ], fn ($v) => $v !== null);

        if (! empty($addressData)) {
            $address = Address::create($addressData);
            $userPayload['address_id'] = $address->id;
            $userPayload['city_id'] = $addressData['city_id'] ?? null;
        }

        $user = User::create($userPayload);

        // Strip virtual fields
        unset(
            $data['user'],
            $data['address_province_id'],
            $data['address_city_id'],
            $data['address_sub_district_id'],
            $data['address_village_id'],
            $data['address_street'],
            $data['address_postal_code'],
        );

        return $user->teacher()->create($data);
    }
}
