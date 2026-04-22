<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record->user;

        $data['user'] = [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->gender,
            'phone_number' => $user->phone_number,
            'date_of_birth' => $user->date_of_birth,
            'place_of_birth' => $user->place_of_birth,
        ];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $userData = $data['user'] ?? [];

        $record->user->update([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'gender' => $userData['gender'] ?? null,
            'phone_number' => $userData['phone_number'] ?? null,
            'date_of_birth' => $userData['date_of_birth'] ?? null,
            'place_of_birth' => $userData['place_of_birth'] ?? null,
        ]);

        unset($data['user']);

        $record->update($data);

        return $record;
    }
}
