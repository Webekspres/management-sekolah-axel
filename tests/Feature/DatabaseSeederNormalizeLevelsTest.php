<?php

use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Subject;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;

test('normalisasi level menggabungkan data duplikat lintas variasi nama', function () {
    $duplicateSd = Level::factory()->create([
        'name' => 'sd',
        'default_spp' => 100000,
    ]);
    $duplicateSmp = Level::factory()->create([
        'name' => 'Sekolah Menengah Pertama',
        'default_spp' => 111000,
    ]);
    Level::factory()->create([
        'name' => 'SMA',
        'default_spp' => 125000,
    ]);
    Level::factory()->create([
        'name' => ' SD ',
        'default_spp' => 130000,
    ]);

    $schoolClass = SchoolClass::factory()->create([
        'level_id' => $duplicateSd->id,
    ]);
    $subject = Subject::factory()->create([
        'level_id' => $duplicateSmp->id,
    ]);

    $seeder = new class extends DatabaseSeeder
    {
        public function runNormalizeLevelsForTest(): Collection
        {
            return $this->normalizeLevels();
        }
    };

    $seeder->runNormalizeLevelsForTest();

    expect(Level::query()->count())->toBe(3);
    expect(Level::query()->pluck('name')->sort()->values()->all())
        ->toBe(['SD', 'SMA', 'SMP']);

    $canonicalSd = Level::query()->where('name', 'SD')->firstOrFail();
    $canonicalSmp = Level::query()->where('name', 'SMP')->firstOrFail();
    $canonicalSma = Level::query()->where('name', 'SMA')->firstOrFail();

    expect((float) $canonicalSd->default_spp)->toBe(150000.0);
    expect((float) $canonicalSmp->default_spp)->toBe(250000.0);
    expect((float) $canonicalSma->default_spp)->toBe(350000.0);

    expect($schoolClass->fresh()->level_id)->toBe($canonicalSd->id);
    expect($subject->fresh()->level_id)->toBe($canonicalSmp->id);
});
