<?php

use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\assertDatabaseMissing;

test('student dihapus ikut menghapus akun user agar email bisa dipakai ulang', function () {
    $student = Student::factory()->create();
    $email = $student->user->email;

    $student->delete();

    assertDatabaseMissing('students', [
        'id' => $student->id,
    ]);

    assertDatabaseMissing('users', [
        'id' => $student->user_id,
    ]);

    $user = User::factory()->asSiswa()->create([
        'email' => $email,
    ]);

    expect($user->email)->toBe($email);
});
