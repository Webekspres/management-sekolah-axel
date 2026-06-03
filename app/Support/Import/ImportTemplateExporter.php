<?php

namespace App\Support\Import;

use App\Models\Level;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportTemplateExporter
{
    private const GUIDE_COLUMN_COUNT = 5;

    /**
     * @param  'student'|'teacher'  $type
     */
    public function downloadFilename(string $type): string
    {
        return $type === 'teacher'
            ? 'template-import-guru.xlsx'
            : 'template-import-siswa.xlsx';
    }

    /**
     * @param  'student'|'teacher'  $type
     */
    public function cachedPath(string $type, ?string $academicLevelId = null): string
    {
        if ($type === 'teacher') {
            return $this->cacheDirectory().'/template-import-guru.xlsx';
        }

        $levelSlug = $this->resolveLevelSlug($academicLevelId);

        return $this->cacheDirectory()."/template-import-siswa-{$levelSlug}.xlsx";
    }

    /**
     * @param  'student'|'teacher'  $type
     */
    public function isCached(string $type, ?string $academicLevelId = null): bool
    {
        return is_file($this->cachedPath($type, $academicLevelId));
    }

    /**
     * @return list<array{type: 'student'|'teacher', level_id: string|null}>
     */
    public function cacheTargets(): array
    {
        $targets = [
            ['type' => 'teacher', 'level_id' => null],
        ];

        foreach (Level::query()->orderedForDisplay()->get() as $level) {
            $targets[] = ['type' => 'student', 'level_id' => $level->id];
        }

        return $targets;
    }

    public function warmAll(): int
    {
        $built = 0;

        foreach ($this->cacheTargets() as $target) {
            $this->warm($target['type'], $target['level_id']);
            $built++;
        }

        return $built;
    }

    /**
     * @param  'student'|'teacher'  $type
     */
    public function warm(string $type, ?string $academicLevelId = null): string
    {
        $path = $this->cachedPath($type, $academicLevelId);

        $this->buildFile($path, $type, $academicLevelId);

        return $path;
    }

    /**
     * @param  'student'|'teacher'  $type
     */
    public function download(string $type, ?string $academicLevelId = null): BinaryFileResponse
    {
        $path = $this->cachedPath($type, $academicLevelId);

        abort_unless(
            is_file($path),
            404,
            __('personalia.import.errors.template_not_cached'),
        );

        return response()->download($path, $this->downloadFilename($type), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  'student'|'teacher'  $type
     */
    public function buildFile(string $path, string $type, ?string $academicLevelId = null): void
    {
        File::ensureDirectoryExists(dirname($path));

        $writer = new Writer;
        $writer->openToFile($path);

        $this->writeDataSheet($writer, $type);
        $this->writeGuideSheet($writer, $type);
        $this->writeChoicesSheet($writer, $type, $academicLevelId);
        $this->writeRegionsSheet($writer);

        $writer->close();
    }

    private function cacheDirectory(): string
    {
        return storage_path('app/import-templates');
    }

    private function resolveLevelSlug(?string $academicLevelId): string
    {
        if (blank($academicLevelId)) {
            return 'semua';
        }

        $levelName = Level::query()->find($academicLevelId)?->name;

        return Str::slug($levelName ?? 'unknown');
    }

    /**
     * @param  'student'|'teacher'  $type
     */
    private function writeDataSheet(Writer $writer, string $type): void
    {
        $writer->getCurrentSheet()->setName(__('personalia.import.sheet.data'));

        $definitions = $type === 'teacher'
            ? ImportColumnCatalog::teacherColumns()
            : ImportColumnCatalog::studentColumns();

        $hintStyle = (new Style)->setFontItalic()->setFontColor(Color::rgb(100, 100, 100));

        $writer->addRow(Row::fromValues(array_map(
            fn (ImportColumnDefinition $column): string => $column->label(),
            $definitions,
        )));

        $writer->addRow(Row::fromValuesWithStyles(
            array_map(fn (ImportColumnDefinition $column): string => $column->formatHint(), $definitions),
            $hintStyle,
        ));

        $writer->addRow(Row::fromValues(array_map(
            fn (ImportColumnDefinition $column): string => $column->example() ?? '',
            $definitions,
        )));
    }

    /**
     * @param  'student'|'teacher'  $type
     */
    private function writeGuideSheet(Writer $writer, string $type): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName(__('personalia.import.sheet.guide'));

        $warningStyle = (new Style)->setFontColor(Color::rgb(180, 83, 9));

        if ($type === 'student') {
            $this->addGuideSectionTitle($writer, __('personalia.import.guide.sections.important'));
            $this->addGuideTextRow($writer, __('personalia.import.warnings.class_scope'), $warningStyle);
            $this->addGuideTextRow($writer, __('personalia.import.warnings.class_duplicate'), $warningStyle);
            $this->addGuideEmptyRow($writer);
        }

        $this->addGuideSectionTitle($writer, __('personalia.import.guide.sections.steps'));

        $steps = __('personalia.import.guide.steps');

        if (is_array($steps)) {
            foreach ($steps as $index => $step) {
                $this->addGuideTextRow($writer, ((string) ($index + 1)).'. '.$step);
            }
        }

        $this->addGuideEmptyRow($writer);

        $definitions = $type === 'teacher'
            ? ImportColumnCatalog::teacherColumns()
            : ImportColumnCatalog::studentColumns();

        $writer->addRow(Row::fromValues([
            __('personalia.import.guide.columns.column'),
            __('personalia.import.guide.columns.how_to_fill'),
            __('personalia.import.guide.columns.example'),
            __('personalia.import.guide.columns.required'),
            __('personalia.import.guide.columns.notes'),
        ]));

        foreach ($definitions as $definition) {
            $writer->addRow(Row::fromValues([
                $definition->label(),
                $definition->formatHint(),
                $definition->example() ?? '',
                $definition->required
                    ? __('personalia.import.guide.columns.yes')
                    : __('personalia.import.guide.columns.no'),
                '',
            ]));
        }
    }

    private function addGuideSectionTitle(Writer $writer, string $title): void
    {
        $values = array_fill(0, self::GUIDE_COLUMN_COUNT, '');
        $values[0] = $title;

        $writer->addRow(Row::fromValuesWithStyles(
            $values,
            null,
            [0 => (new Style)->setFontBold()],
        ));
    }

    private function addGuideTextRow(Writer $writer, string $text, ?Style $style = null): void
    {
        $values = array_fill(0, self::GUIDE_COLUMN_COUNT, '');
        $values[1] = $text;

        $columnStyles = $style !== null ? [1 => $style] : [];

        $writer->addRow(Row::fromValuesWithStyles($values, null, $columnStyles));
    }

    private function addGuideEmptyRow(Writer $writer): void
    {
        $writer->addRow(Row::fromValues(array_fill(0, self::GUIDE_COLUMN_COUNT, '')));
    }

    /**
     * @param  'student'|'teacher'  $type
     */
    private function writeChoicesSheet(Writer $writer, string $type, ?string $academicLevelId): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName(__('personalia.import.sheet.choices'));

        $infoStyle = (new Style)->setFontItalic()->setFontColor(Color::rgb(80, 80, 80));

        $this->addGuideSectionTitle($writer, __('personalia.import.choices.notes_title'));
        $this->addGuideTextRow($writer, __('personalia.import.choices.notes_sync'), $infoStyle);
        $this->addGuideTextRow($writer, __('personalia.import.choices.notes_empty'), $infoStyle);
        $this->addGuideEmptyRow($writer);

        $levelName = $academicLevelId
            ? Level::query()->find($academicLevelId)?->name
            : null;

        if ($type === 'student' && $levelName) {
            $this->addGuideTextRow($writer, __('personalia.import.choices.level_heading', ['level' => $levelName]));
            $this->addGuideEmptyRow($writer);
        }

        $writer->addRow(Row::fromValues([
            __('personalia.import.choices.gender'),
            __('personalia.import.choices.religion'),
            $type === 'teacher' ? __('personalia.import.choices.employment_status') : __('personalia.import.choices.classes'),
        ]));

        $genders = ['L', 'P'];
        $religions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
        $employment = ['Staff TU', 'Guru Kelas', 'Lainnya'];

        $classes = $type === 'student'
            ? SchoolClass::query()
                ->withoutGlobalScopes()
                ->when($academicLevelId, fn ($query) => $query->where('level_id', $academicLevelId))
                ->orderBy('name')
                ->pluck('name')
                ->all()
            : [];

        $maxRows = max(count($genders), count($religions), count($classes), count($employment));

        for ($i = 0; $i < $maxRows; $i++) {
            $writer->addRow(Row::fromValues([
                $genders[$i] ?? '',
                $religions[$i] ?? '',
                $type === 'teacher'
                    ? ($employment[$i] ?? '')
                    : ($classes[$i] ?? ''),
            ]));
        }
    }

    private function writeRegionsSheet(Writer $writer): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName(__('personalia.import.sheet.regions'));

        $writer->addRow(Row::fromValues([
            __('personalia.import.columns.provinsi'),
            __('personalia.import.columns.kota_kabupaten'),
            __('personalia.import.columns.kecamatan'),
            __('personalia.import.columns.desa_kelurahan'),
        ]));

        DB::table('villages')
            ->join('sub_districts', 'villages.sub_district_id', '=', 'sub_districts.id')
            ->join('cities', 'sub_districts.city_id', '=', 'cities.id')
            ->join('provinces', 'cities.province_id', '=', 'provinces.id')
            ->orderBy('provinces.name')
            ->orderBy('cities.name')
            ->orderBy('sub_districts.name')
            ->orderBy('villages.name')
            ->select([
                'provinces.name as province_name',
                'cities.name as city_name',
                'sub_districts.name as sub_district_name',
                'villages.name as village_name',
            ])
            ->lazy()
            ->each(function (object $row) use ($writer): void {
                $writer->addRow(Row::fromValues([
                    $row->province_name,
                    $row->city_name,
                    $row->sub_district_name,
                    $row->village_name,
                ]));
            });
    }
}
