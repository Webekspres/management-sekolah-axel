<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
use App\Models\Address;
use App\Models\City;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    public function getTitle(): string
    {
        return 'Ubah Guru';
    }

    public function form(Schema $schema): Schema
    {
        $schema = TeacherForm::configure($schema);

        $schema->components([
            ...$schema->getComponents(),
            Section::make('Informasi Data')
                ->collapsible()
                ->schema([
                    Placeholder::make('created_at')
                        ->label('Dibuat')
                        ->content(fn () => $this->record->user->created_at?->format('d M Y H:i') ?? '-'),
                    Placeholder::make('updated_at')
                        ->label('Diperbarui')
                        ->content(fn () => $this->record->user->updated_at?->format('d M Y H:i') ?? '-'),
                ]),
        ]);

        return $schema;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record->user;

        // Resolve birth province from place_of_birth city name
        $birthProvinceId = null;
        if ($user->place_of_birth) {
            $birthCity = City::where('name', $user->place_of_birth)->first();
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

        // Resolve address cascading fields
        $address = $user->address;
        if ($address) {
            $data['address_province_id'] = $address->province_id;
            $data['address_city_id'] = $address->city_id;
            $data['address_sub_district_id'] = $address->sub_district_id;
            $data['address_village_id'] = $address->village_id;
            $data['address_street'] = $address->street;
            $data['address_postal_code'] = $address->postal_code;
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

        $birthCity = filled($userData['place_of_birth'] ?? null)
            ? City::query()->where('name', $userData['place_of_birth'])->first()
            : null;

        // Handle address
        $addressData = array_filter([
            'province_id' => $data['address_province_id'] ?? null,
            'city_id' => $data['address_city_id'] ?? null,
            'sub_district_id' => $data['address_sub_district_id'] ?? null,
            'village_id' => $data['address_village_id'] ?? null,
            'street' => $data['address_street'] ?? null,
            'postal_code' => $data['address_postal_code'] ?? null,
        ], fn ($v) => $v !== null);

        // Strip virtual fields before saving teacher record
        unset(
            $data['user'],
            $data['address_province_id'],
            $data['address_city_id'],
            $data['address_sub_district_id'],
            $data['address_village_id'],
            $data['address_street'],
            $data['address_postal_code'],
        );

        return DB::transaction(function () use ($addressData, $birthCity, $data, $record, $userData): Model {
            $userPayload = [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'gender' => $userData['gender'] ?? 'L',
                'phone_number' => $userData['phone_number'] ?? null,
                'date_of_birth' => $userData['date_of_birth'] ?? null,
                'place_of_birth' => $userData['place_of_birth'] ?? null,
                'address_detail' => $userData['address_detail'] ?? null,
                'city_id' => $birthCity?->id,
                'role' => 'guru',
            ];

            if (filled($userData['password'] ?? null)) {
                $userPayload['password'] = $userData['password'];
            }

            if (! empty($addressData)) {
                if ($record->user->address_id) {
                    $record->user->address->update($addressData);
                } else {
                    $address = Address::query()->create($addressData);
                    $userPayload['address_id'] = $address->id;
                }

                $userPayload['city_id'] = $addressData['city_id'] ?? $userPayload['city_id'];
            }

            $record->user->update($userPayload);
            $record->update($data);

            return $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
