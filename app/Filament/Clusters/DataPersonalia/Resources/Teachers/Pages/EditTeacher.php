<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

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

        $updatePayload = [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'gender' => $userData['gender'] ?? 'L',
            'phone_number' => $userData['phone_number'] ?? null,
            'date_of_birth' => $userData['date_of_birth'] ?? null,
            'place_of_birth' => $userData['place_of_birth'] ?? null,
        ];

        if (! empty($userData['password'])) {
            $updatePayload['password'] = Hash::make($userData['password']);
        }

        $record->user->update($updatePayload);

        unset($data['user']);

        $record->update($data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
