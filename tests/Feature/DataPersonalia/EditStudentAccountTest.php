<?php

use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\EditStudent;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->asAdmin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('admin dapat memperbarui password akun terpadu siswa dan orang tua', function () {
    $student = Student::factory()->create();

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->fillForm([
            'user.birth_province_id' => null,
            'user.place_of_birth' => null,
            'user.password' => 'password-baru-123',
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(Hash::check('password-baru-123', $student->user->refresh()->password))->toBeTrue()
        ->and($student->user->role)->toBe('siswa_ortu');
});
