<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use App\Models\City;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $userData = $data['user'] ?? [];

        if (User::query()->where('email', $userData['email'] ?? null)->exists()) {
            throw ValidationException::withMessages([
                'user.email' => 'Email sudah digunakan.',
            ]);
        }

        unset(
            $data['user'],
            $data['address_province_id'],
            $data['address_city_id'],
            $data['address_sub_district_id'],
            $data['address_village_id'],
        );

        return DB::transaction(function () use ($data, $userData): Model {
            $birthCity = filled($userData['place_of_birth'] ?? null)
                ? City::query()->where('name', $userData['place_of_birth'])->first()
                : null;

            $user = User::query()->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'gender' => $userData['gender'] ?? 'L',
                'phone_number' => $userData['phone_number'] ?? null,
                'date_of_birth' => $userData['date_of_birth'] ?? null,
                'place_of_birth' => $userData['place_of_birth'] ?? null,
                'address_detail' => $userData['address_detail'] ?? null,
                'city_id' => $birthCity?->id,
                'role' => 'siswa_ortu',
            ]);

            return $user->student()->create($data);
        });
    }
}
