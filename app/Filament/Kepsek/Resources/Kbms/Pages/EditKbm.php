<?php

namespace App\Filament\Kepsek\Resources\Kbms\Pages;

use App\Filament\Kepsek\Resources\Kbms\KbmResource;
use App\Models\Kbm;
use DomainException;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditKbm extends EditRecord
{
    protected static string $resource = KbmResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Kbm $record */
        $targetStatus = $data['status'];
        $revisionNote = $data['revision_note'] ?? null;

        try {
            if (! in_array($record->status, ['PENDING', 'REVISED'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Kepsek hanya dapat menindaklanjuti laporan KBM yang sudah diajukan (status Pending atau Revisi).',
                ]);
            }

            if ($targetStatus === 'REVISED') {
                if (blank($revisionNote)) {
                    throw ValidationException::withMessages([
                        'revision_note' => 'Catatan perubahan wajib diisi ketika meminta revisi.',
                    ]);
                }

                $record->markAsRevised(
                    actor: auth()->user(),
                    revisionNote: $revisionNote,
                );

                return $record->refresh();
            }

            if ($targetStatus === 'APPROVED') {
                $record->approve(auth()->user());

                return $record->refresh();
            }

            throw ValidationException::withMessages([
                'status' => 'Kepsek hanya dapat mengubah status menjadi Revisi atau Approved.',
            ]);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'status' => $exception->getMessage(),
            ]);
        }
    }
}
