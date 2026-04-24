<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use App\Models\City;
use App\Models\SubDistrict;
use App\Models\User;
use App\Models\Village;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    public function getTitle(): string
    {
        return 'Ubah Siswa';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record->user;

        // Resolve birth province from city_id or place_of_birth text
        $birthProvinceId = null;
        if ($user->city_id) {
            $birthCity = City::find($user->city_id);
            $birthProvinceId = $birthCity?->province_id;
        }

        $data['user'] = [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->gender,
            'phone_number' => $user->phone_number,
            'date_of_birth' => $user->date_of_birth,
            'place_of_birth' => $user->place_of_birth,
            'address_detail' => $user->address_detail,
            'birth_province_id' => $birthProvinceId,
        ];

        // Resolve address cascading fields from stored text values
        $cityName = $this->record->city;
        $districtName = $this->record->district;
        $villageName = $this->record->village;

        if ($cityName) {
            $city = City::where('name', $cityName)->first();
            if ($city) {
                $data['address_province_id'] = $city->province_id;
                $data['address_city_id'] = $city->id;

                if ($districtName) {
                    $subDistrict = SubDistrict::where('city_id', $city->id)
                        ->where('name', $districtName)
                        ->first();
                    if ($subDistrict) {
                        $data['address_sub_district_id'] = $subDistrict->id;

                        if ($villageName) {
                            $village = Village::where('sub_district_id', $subDistrict->id)
                                ->where('name', $villageName)
                                ->first();
                            if ($village) {
                                $data['address_village_id'] = $village->id;
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $userData = $data['user'] ?? [];

        $emailExists = User::query()
            ->where('email', $userData['email'] ?? null)
            ->whereKeyNot($record->user_id)
            ->exists();

        if ($emailExists) {
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

        return DB::transaction(function () use ($data, $record, $userData): Model {
            $birthCity = filled($userData['place_of_birth'] ?? null)
                ? City::query()->where('name', $userData['place_of_birth'])->first()
                : null;

            $userPayload = [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'gender' => $userData['gender'] ?? null,
                'phone_number' => $userData['phone_number'] ?? null,
                'date_of_birth' => $userData['date_of_birth'] ?? null,
                'place_of_birth' => $userData['place_of_birth'] ?? null,
                'address_detail' => $userData['address_detail'] ?? null,
                'city_id' => $birthCity?->id,
                'role' => 'siswa_ortu',
            ];

            if (filled($userData['password'] ?? null)) {
                $userPayload['password'] = $userData['password'];
            }

            $record->user->update($userPayload);
            $record->update($data);

            return $record;
        });
    }
}
