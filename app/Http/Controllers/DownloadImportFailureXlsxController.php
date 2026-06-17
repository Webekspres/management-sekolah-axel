<?php

namespace App\Http\Controllers;

use App\Support\Import\FailedImportRowsExporter;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadImportFailureXlsxController extends Controller
{
    public function __invoke(Request $request, Import $import, FailedImportRowsExporter $exporter): BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(absolute: false), 403);

        abort_unless(auth(
            $request->hasValidSignature(absolute: false)
                ? $request->query('authGuard')
                : null,
        )->check(), 401);

        $user = auth(
            $request->hasValidSignature(absolute: false)
                ? $request->query('authGuard')
                : null,
        )->user();

        $importPolicy = Gate::getPolicyFor($import::class);

        if (filled($importPolicy) && method_exists($importPolicy, 'view')) {
            Gate::forUser($user)->authorize('view', Arr::wrap($import));
        } else {
            abort_unless($import->user()->is($user), 403);
        }

        abort_unless($import->getFailedRowsCount() > 0, 404);

        $tempPath = $exporter->buildToTempFile($import);

        return response()->download(
            $tempPath,
            $exporter->downloadFilename($import),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend();
    }
}
