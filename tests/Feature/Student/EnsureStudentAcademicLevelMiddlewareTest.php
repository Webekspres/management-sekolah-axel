<?php

use App\Http\Middleware\EnsureStudentAcademicLevel;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('middleware memaksa active academic level ke jenjang siswa', function () {
    $levelSmp = Level::factory()->create(['name' => 'SMP']);
    $levelSma = Level::factory()->create(['name' => 'SMA']);
    $user = User::factory()->asSiswa()->create();
    $schoolClass = SchoolClass::factory()->create(['level_id' => $levelSmp->id]);

    Student::factory()->create([
        'user_id' => $user->id,
        'class_id' => $schoolClass->id,
    ]);

    actingAs($user);
    session(['active_academic_level_id' => (string) $levelSma->id]);

    $request = Request::create('/student', 'GET');

    app(EnsureStudentAcademicLevel::class)->handle($request, fn () => response('ok'));

    expect(session('active_academic_level_id'))->toBe((string) $levelSmp->id);
});

test('middleware menghapus active academic level ketika siswa belum terhubung class', function () {
    $user = User::factory()->asSiswa()->create();

    actingAs($user);
    session(['active_academic_level_id' => 'invalid-level']);

    $request = Request::create('/student', 'GET');

    app(EnsureStudentAcademicLevel::class)->handle($request, fn () => response('ok'));

    expect(session()->has('active_academic_level_id'))->toBeFalse();
});
