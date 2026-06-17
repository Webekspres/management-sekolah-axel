<?php

namespace App\Filament\Imports\Concerns;

use Filament\Actions\Action;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

trait HasPersonaliaImportNotifications
{
    public static function getCompletedNotificationTitle(Import $import): string
    {
        $failedRowsCount = $import->getFailedRowsCount();

        if ($failedRowsCount === $import->total_rows) {
            return __('personalia.import.notifications.completed_all_failed_title');
        }

        if ($failedRowsCount > 0) {
            return __('personalia.import.notifications.completed_partial_title');
        }

        return __('personalia.import.notifications.completed_success_title');
    }

    public static function modifyCompletedNotification(Notification $notification, Import $import): Notification
    {
        $failedRowsCount = $import->getFailedRowsCount();
        $successfulRows = $import->successful_rows;
        $totalRows = max($import->total_rows, 1);

        $notification->body(new HtmlString(view('filament.import.personalia-result', [
            'totalRows' => $import->total_rows,
            'successfulRows' => $successfulRows,
            'failedRows' => $failedRowsCount,
            'successPercent' => (int) round(($successfulRows / $totalRows) * 100),
            'failedPercent' => (int) round(($failedRowsCount / $totalRows) * 100),
        ])->render()));

        if ($failedRowsCount > 0) {
            $notification->actions([
                Action::make('downloadFailedRowsXlsx')
                    ->label(__('personalia.import.notifications.download_failed_rows_xlsx'))
                    ->color('danger')
                    ->url(URL::signedRoute('personalia.imports.failed-rows.download', [
                        'authGuard' => Filament::getAuthGuard(),
                        'import' => $import,
                    ], absolute: false), shouldOpenInNewTab: true)
                    ->markAsRead(),
            ]);
        }

        return $notification;
    }
}
