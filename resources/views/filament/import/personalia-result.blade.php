<div class="fi-import-result space-y-3 text-sm">
    <div class="flex h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        @if ($successfulRows > 0)
            <div
                class="h-full bg-success-500 transition-all"
                style="width: {{ $successPercent }}%"
                title="{{ __('personalia.import.progress.successful', ['count' => number_format($successfulRows)]) }}"
            ></div>
        @endif
        @if ($failedRows > 0)
            <div
                class="h-full bg-danger-500 transition-all"
                style="width: {{ $failedPercent }}%"
                title="{{ __('personalia.import.progress.failed', ['count' => number_format($failedRows)]) }}"
            ></div>
        @endif
    </div>

    <div class="grid grid-cols-3 gap-2 text-center">
        <div class="rounded-lg bg-gray-50 px-2 py-2 dark:bg-gray-800">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('personalia.import.progress.total') }}</div>
            <div class="text-base font-semibold text-gray-950 dark:text-white">{{ number_format($totalRows) }}</div>
        </div>
        <div class="rounded-lg bg-success-50 px-2 py-2 dark:bg-success-950/30">
            <div class="text-xs text-success-700 dark:text-success-400">{{ __('personalia.import.progress.successful_label') }}</div>
            <div class="text-base font-semibold text-success-700 dark:text-success-400">{{ number_format($successfulRows) }}</div>
        </div>
        <div class="rounded-lg bg-danger-50 px-2 py-2 dark:bg-danger-950/30">
            <div class="text-xs text-danger-700 dark:text-danger-400">{{ __('personalia.import.progress.failed_label') }}</div>
            <div class="text-base font-semibold text-danger-700 dark:text-danger-400">{{ number_format($failedRows) }}</div>
        </div>
    </div>
</div>
