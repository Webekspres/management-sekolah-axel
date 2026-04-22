<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $userData = $data['user'] ?? [];

        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'gender' => $userData['gender'] ?? 'L',
            'phone_number' => $userData['phone_number'] ?? null,
            'date_of_birth' => $userData['date_of_birth'] ?? null,
            'place_of_birth' => $userData['place_of_birth'] ?? null,
            'role' => 'siswa_ortu',
        ]);

        unset($data['user']);

        return $user->student()->create($data);
    }
}
