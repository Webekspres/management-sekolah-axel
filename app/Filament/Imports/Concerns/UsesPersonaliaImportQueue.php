<?php

namespace App\Filament\Imports\Concerns;

trait UsesPersonaliaImportQueue
{
    public function getJobConnection(): ?string
    {
        return config('personalia.import_queue_connection');
    }
}
