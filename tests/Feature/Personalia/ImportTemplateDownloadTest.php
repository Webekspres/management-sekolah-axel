<?php

use App\Models\City;
use App\Models\Level;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\User;
use App\Models\Village;
use App\Support\Import\ImportTemplateExporter;
use OpenSpout\Reader\Common\Creator\ReaderFactory;

/**
 * @return list<list<string>>
 */
function readImportTemplateRegionsSheet(string $path): array
{
    $reader = ReaderFactory::createFromFile($path);
    $reader->open($path);

    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        if ($sheet->getName() !== __('personalia.import.sheet.regions')) {
            continue;
        }

        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = array_map(
                fn ($cell): string => trim((string) ($cell?->getValue() ?? '')),
                $row->getCells(),
            );
        }

        break;
    }

    $reader->close();

    return $rows;
}

test('regions csv cache is reused across template builds', function () {
    $province = Province::factory()->create(['name' => 'Jawa Barat CSV']);
    $city = City::factory()->create(['province_id' => $province->id, 'name' => 'Kota Bandung CSV']);
    $subDistrict = SubDistrict::factory()->create(['city_id' => $city->id, 'name' => 'Coblong CSV']);
    Village::factory()->create(['sub_district_id' => $subDistrict->id, 'name' => 'Dago CSV']);

    $exporter = app(ImportTemplateExporter::class);
    $regionsCsv = $exporter->regionsCsvPath();

    if (is_file($regionsCsv)) {
        unlink($regionsCsv);
    }

    $exporter->warm('teacher', null, includeFullRegions: true);

    expect(is_file($regionsCsv))->toBeTrue();

    $firstModified = filemtime($regionsCsv);

    $exporter->warm('teacher', null, includeFullRegions: true);

    expect(filemtime($regionsCsv))->toBe($firstModified);
});

test('cached import template includes wilayah rows when built with full regions', function () {
    $exporter = app(ImportTemplateExporter::class);

    if (is_file($exporter->regionsCsvPath())) {
        unlink($exporter->regionsCsvPath());
    }

    $province = Province::factory()->create(['name' => 'Jawa Barat Template']);
    $city = City::factory()->create(['province_id' => $province->id, 'name' => 'Kota Bandung Template']);
    $subDistrict = SubDistrict::factory()->create(['city_id' => $city->id, 'name' => 'Coblong Template']);
    Village::factory()->create(['sub_district_id' => $subDistrict->id, 'name' => 'Dago Template']);

    $path = $exporter->warm('teacher', null, includeFullRegions: true);

    $rows = readImportTemplateRegionsSheet($path);

    expect($rows)->not->toBeEmpty()
        ->and(collect($rows)->contains(fn (array $row): bool => $row[0] === 'Jawa Barat Template'
            && $row[1] === 'Kota Bandung Template'
            && $row[2] === 'Coblong Template'
            && $row[3] === 'Dago Template'))->toBeTrue();
});

test('browser fallback template does not include wilayah rows', function () {
    Province::factory()->create(['name' => 'Jawa Barat Web']);

    $exporter = app(ImportTemplateExporter::class);
    $path = $exporter->warm('teacher', null, includeFullRegions: false);

    $rows = readImportTemplateRegionsSheet($path);

    expect($rows)->toHaveCount(2)
        ->and($rows[1][1])->toContain(__('personalia.import.regions.web_limited_note'));
});

test('download serves pre-cached template without stripping wilayah data', function () {
    $admin = User::factory()->asAdmin()->create();
    $level = Level::factory()->create(['name' => 'SD']);
    $exporter = app(ImportTemplateExporter::class);

    if (is_file($exporter->regionsCsvPath())) {
        unlink($exporter->regionsCsvPath());
    }

    $province = Province::factory()->create(['name' => 'Jawa Tengah Cached']);
    $city = City::factory()->create(['province_id' => $province->id, 'name' => 'Kota Semarang Cached']);
    $subDistrict = SubDistrict::factory()->create(['city_id' => $city->id, 'name' => 'Candisari Cached']);
    Village::factory()->create(['sub_district_id' => $subDistrict->id, 'name' => 'Jatingaleh Cached']);

    $cachedPath = $exporter->warm('student', $level->id, includeFullRegions: true);

    $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $level->id])
        ->get(route('personalia.import-template', ['type' => 'student']))
        ->assertOk()
        ->assertDownload('template-import-siswa.xlsx');

    $rows = readImportTemplateRegionsSheet($cachedPath);

    expect(collect($rows)->contains(fn (array $row): bool => $row[3] === 'Jatingaleh Cached'))->toBeTrue();
});

test('cache command builds import template files', function () {
    Level::factory()->create(['name' => 'SD']);
    Level::factory()->create(['name' => 'SMP']);

    $this->artisan('personalia:cache-import-templates')
        ->assertSuccessful();

    $exporter = app(ImportTemplateExporter::class);

    expect($exporter->isCached('teacher', null))->toBeTrue()
        ->and($exporter->isCached('student', Level::query()->where('name', 'SD')->value('id')))->toBeTrue();
});

test('admin can download pre-cached student import template', function () {
    $admin = User::factory()->asAdmin()->create();
    $level = Level::factory()->create(['name' => 'SD']);
    $exporter = app(ImportTemplateExporter::class);

    $exporter->warm('student', $level->id);

    $response = $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $level->id])
        ->get(route('personalia.import-template', ['type' => 'student']));

    $response->assertOk();
    $response->assertDownload('template-import-siswa.xlsx');

    expect(filesize($exporter->cachedPath('student', $level->id)))->toBeGreaterThan(1024);
});

test('admin can download pre-cached teacher import template', function () {
    $admin = User::factory()->asAdmin()->create();

    app(ImportTemplateExporter::class)->warm('teacher', null);

    $response = $this->actingAs($admin)
        ->get(route('personalia.import-template', ['type' => 'teacher']));

    $response->assertOk();
    $response->assertDownload('template-import-guru.xlsx');
});

test('download builds template on demand when not cached', function () {
    $admin = User::factory()->asAdmin()->create();
    $level = Level::factory()->create(['name' => 'SMA']);

    $cacheDir = storage_path('app/import-templates');
    if (is_dir($cacheDir)) {
        foreach (glob($cacheDir.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $level->id])
        ->get(route('personalia.import-template', ['type' => 'student']))
        ->assertOk()
        ->assertDownload('template-import-siswa.xlsx');
});

test('download rebuilds template when cached file is empty', function () {
    $admin = User::factory()->asAdmin()->create();
    $level = Level::factory()->create(['name' => 'SMA']);
    $exporter = app(ImportTemplateExporter::class);
    $cachedPath = $exporter->cachedPath('student', $level->id);

    $cacheDir = dirname($cachedPath);
    if (! is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    file_put_contents($cachedPath, '');

    $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $level->id])
        ->get(route('personalia.import-template', ['type' => 'student']))
        ->assertOk()
        ->assertDownload('template-import-siswa.xlsx');

    expect(filesize($cachedPath))->toBeGreaterThan(0);
});

test('guest cannot download import template', function () {
    $this->get(route('personalia.import-template', ['type' => 'student']))
        ->assertRedirect('/login');
});

test('non admin cannot download import template', function () {
    $guru = User::factory()->asGuru()->create();

    $this->actingAs($guru)
        ->get(route('personalia.import-template', ['type' => 'student']))
        ->assertForbidden();
});
