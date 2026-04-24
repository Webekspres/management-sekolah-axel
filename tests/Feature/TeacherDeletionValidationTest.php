<?php

use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

test('teacher tidak bisa dihapus jika masih dipakai oleh data akademik', function () {
    $teacher = Teacher::factory()->create();

    SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
    ]);

    expect(fn () => $teacher->delete())
        ->toThrow(ValidationException::class);
});

test('teacher bisa dihapus jika tidak punya relasi pemakaian akademik', function () {
    $teacher = Teacher::factory()->create();

    $teacher->delete();

    $this->assertDatabaseMissing('teachers', [
        'id' => $teacher->id,
    ]);
});
