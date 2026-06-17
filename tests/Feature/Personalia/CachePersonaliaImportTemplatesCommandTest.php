<?php

use App\Models\Level;
use App\Support\Import\ImportTemplateExporter;

test('cache command can build a single template target', function () {
    Level::factory()->create(['name' => 'SD']);
    Level::factory()->create(['name' => 'SMP']);

    $exporter = app(ImportTemplateExporter::class);

    $this->artisan('personalia:cache-import-templates', ['--target' => 'teacher'])
        ->assertSuccessful();

    expect($exporter->isCached('teacher'))->toBeTrue()
        ->and($exporter->isCached('student', Level::query()->where('name', 'SD')->value('id')))->toBeFalse();
});
