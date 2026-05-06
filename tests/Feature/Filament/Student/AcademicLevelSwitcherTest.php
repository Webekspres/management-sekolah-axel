<?php

use App\Livewire\AcademicLevelSwitcher;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('siswa dikunci ke jenjang kelasnya pada level switcher', function () {
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

    Livewire::test(AcademicLevelSwitcher::class)
        ->assertSet('isLockedForStudent', true)
        ->assertSet('active_academic_level_id', (string) $levelSmp->id);

    expect(session('active_academic_level_id'))->toBe((string) $levelSmp->id);
});

test('siswa tanpa profil kelas mendapat warning dan level session dibersihkan', function () {
    $user = User::factory()->asSiswa()->create();

    actingAs($user);
    session(['active_academic_level_id' => 'invalid-level']);

    Livewire::test(AcademicLevelSwitcher::class)
        ->assertSet('warningMessage', 'Akun belum terhubung ke data siswa pada jenjang yang sesuai.')
        ->assertSet('active_academic_level_id', null);

    expect(session()->has('active_academic_level_id'))->toBeFalse();
});

test('non siswa tetap bisa mengganti active academic level', function () {
    $levelSd = Level::factory()->create(['name' => 'SD']);
    $levelSmp = Level::factory()->create(['name' => 'SMP']);
    $user = User::factory()->asGuru()->create();

    actingAs($user);
    session(['active_academic_level_id' => (string) $levelSd->id]);

    Livewire::test(AcademicLevelSwitcher::class)
        ->assertSet('isLockedForStudent', false)
        ->set('active_academic_level_id', (string) $levelSmp->id);

    expect(session('active_academic_level_id'))->toBe((string) $levelSmp->id);
});
