<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Import\ImportTemplateExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PersonaliaImportTemplateController extends Controller
{
    public function __invoke(Request $request, string $type, ImportTemplateExporter $exporter): BinaryFileResponse
    {
        abort_unless(in_array($type, ['student', 'teacher'], true), 404);

        /** @var User $user */
        $user = $request->user();

        match ($type) {
            'student' => abort_unless($user->can('create', Student::class), 403),
            'teacher' => abort_unless($user->can('create', Teacher::class), 403),
        };

        $levelId = session('active_academic_level_id');

        if ($type === 'student' && blank($levelId)) {
            abort(422, __('personalia.import.errors.select_level_first'));
        }

        if (! $exporter->isCached($type, $levelId)) {
            $exporter->warm($type, $levelId, includeFullRegions: false);
        }

        return $exporter->download($type, $levelId);
    }
}
