<?php

namespace App\Filament\Kepsek\Resources\LessonPlans\Pages;

use App\Filament\Kepsek\Resources\LessonPlans\LessonPlanResource;
use App\Models\LessonPlan;
use DomainException;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditLessonPlan extends EditRecord
{
    protected static string $resource = LessonPlanResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var LessonPlan $record */
        $targetStatus = $data['status'];
        $revisionNote = $data['revision_note'] ?? null;

        try {
            if ($targetStatus === $record->status) {
                if ($targetStatus === 'REVISED') {
                    if (blank($revisionNote)) {
                        throw ValidationException::withMessages([
                            'revision_note' => 'Catatan perubahan wajib diisi ketika status revisi.',
                        ]);
                    }

                    $record->update(['revision_note' => $revisionNote]);
                }

                return $record->refresh();
            }

            if ($targetStatus === 'REVISED') {
                if (blank($revisionNote)) {
                    throw ValidationException::withMessages([
                        'revision_note' => 'Catatan perubahan wajib diisi ketika status revisi.',
                    ]);
                }

                if ($record->status === 'PENDING') {
                    $record->markAsRevised(
                        actor: auth()->user(),
                        revisionNote: $revisionNote,
                    );
                } else {
                    $record->update([
                        'status' => 'REVISED',
                        'revision_note' => $revisionNote,
                    ]);
                }

                return $record->refresh();
            }

            if ($targetStatus === 'APPROVED') {
                if ($record->status === 'PENDING') {
                    $record->approve(auth()->user());
                } else {
                    $record->update([
                        'status' => 'APPROVED',
                        'revision_note' => null,
                    ]);
                }

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
