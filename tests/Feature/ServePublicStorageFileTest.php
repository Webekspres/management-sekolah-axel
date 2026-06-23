<?php

use Illuminate\Support\Facades\Storage;

test('public disk is served at /storage without signed url', function () {
    Storage::fake('public');

    Storage::disk('public')->put('lesson-plans/rpp-preview.pdf', 'pdf-content');

    $this->get('/storage/lesson-plans/rpp-preview.pdf')
        ->assertOk();
});

test('storage route returns 404 for missing public disk files', function () {
    Storage::fake('public');

    $this->get('/storage/lesson-plans/missing.pdf')->assertNotFound();
});

test('storage route rejects path traversal', function () {
    Storage::fake('public');

    $this->get('/storage/../.env')->assertNotFound();
});
