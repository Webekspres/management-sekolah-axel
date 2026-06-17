<?php

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('teacher dihapus akan melepas wali kelas menjadi null', function () {
    $teacher = Teacher::factory()->create();

    $schoolClass = SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
    ]);

    $teacher->delete();

    assertDatabaseMissing('teachers', [
        'id' => $teacher->id,
    ]);

    assertDatabaseHas('classes', [
        'id' => $schoolClass->id,
        'teacher_id' => null,
    ]);
});

test('teacher dihapus ikut menghapus akun user agar email bisa dipakai ulang', function () {
    $teacher = Teacher::factory()->create();
    $email = $teacher->user->email;

    $teacher->delete();

    assertDatabaseMissing('teachers', [
        'id' => $teacher->id,
    ]);

    assertDatabaseMissing('users', [
        'id' => $teacher->user_id,
    ]);

    $user = User::factory()->asGuru()->create([
        'email' => $email,
    ]);

    expect($user->email)->toBe($email);
});
