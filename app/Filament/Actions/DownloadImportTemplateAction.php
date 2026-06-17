<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DownloadImportTemplateAction
{
    /**
     * @param  'student'|'teacher'  $type
     */
    public static function make(string $type): Action
    {
        return Action::make("download_{$type}_import_template")
            ->label(__('personalia.import.download_template'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->url(fn (): string => route('personalia.import-template', ['type' => $type]))
            ->openUrlInNewTab();
    }
}
