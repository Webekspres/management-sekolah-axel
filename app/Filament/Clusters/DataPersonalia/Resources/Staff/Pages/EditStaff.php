<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Staff\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Staff\Schemas\StaffForm;
use App\Filament\Clusters\DataPersonalia\Resources\Staff\StaffResource;
use App\Models\Address;
use App\Models\City;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    public function getTitle(): string
    {
        return 'Ubah Staff';
    }

    public function form(Schema $schema): Schema
    {
        $schema = StaffForm::configure($schema);

        $schema->components([
            ...$schema->getComponents(),
            Section::make('Informasi Data')
                ->collapsible()
                ->schema([
                    Placeholder::make('created_at')
                        ->label('Dibuat')
                        ->content(fn () => $this->record->created_at?->format('d M Y H:i') ?? '-'),
                    Placeholder::make('updated_at')
                        ->label('Diperbarui')
                        ->content(fn () => $this->record->updated_at?->format('d M Y H:i') ?? '-'),
                ]),
        ]);

        return $schema;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record;

        $birthProvinceId = null;
        if ($user->place_of_birth) {
            $birthCity = City::where('name', $user->place_of_birth)->first();
            $birthProvinceId = $birthCity?->province_id;
        }

        $data['birth_province_id'] = $birthProvinceId;

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

        return DB::transaction(function () use ($addressData, $birthCity, $data, $record): Model {
            $data['city_id'] = $birthCity?->id;

            if (! empty($addressData)) {
                if ($record->address_id) {
                    $record->address->update($addressData);
                } else {
                    $address = Address::query()->create($addressData);
                    $data['address_id'] = $address->id;
                }

                $data['city_id'] = $addressData['city_id'] ?? $data['city_id'];
            }

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
