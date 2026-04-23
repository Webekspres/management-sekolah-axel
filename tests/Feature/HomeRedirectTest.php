<?php

use App\Models\User;

dataset('home redirects', [
    'admin' => [fn (): User => User::factory()->asAdmin()->create(), '/admin'],
    'kepsek' => [fn (): User => User::factory()->asKepalaSekolah()->create(), '/kepsek'],
    'guru' => [fn (): User => User::factory()->asGuru()->create(), '/guru'],
    'student' => [fn (): User => User::factory()->asSiswa()->create(), '/student'],
]);

test('redirects authenticated users to the right panel', function (callable $createUser, string $expectedPath) {
    $this->actingAs($createUser());

    $this->get('/')->assertRedirect($expectedPath);
})->with('home redirects');
