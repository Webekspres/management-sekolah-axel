<?php

use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\EditStudent;
use App\Models\Student;
use App\Models\User;
use App\Support\MonetaryAmount;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->asAdmin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('admin dapat menyimpan custom_spp dengan nilai besar', function () {
    $student = Student::factory()->create(['custom_spp' => null]);

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->fillForm([
            'user.birth_province_id' => null,
            'user.place_of_birth' => null,
            'custom_spp' => '6.000.000.000',
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $student->refresh()->custom_spp)->toBe(6_000_000_000.0);
});

test('custom_spp tetap benar setelah simpan dan buka ulang form edit', function () {
    $student = Student::factory()->create(['custom_spp' => null]);

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->fillForm([
            'user.birth_province_id' => null,
            'user.place_of_birth' => null,
            'custom_spp' => '6.000',
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $student->refresh()->custom_spp)->toBe(6000.0);

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->assertSet('data.custom_spp', '6.000');
});

test('custom_spp di atas batas kolom ditolak validasi form', function () {
    $student = Student::factory()->create();

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->fillForm([
            'user.birth_province_id' => null,
            'user.place_of_birth' => null,
            'custom_spp' => (string) (MonetaryAmount::MAX + 1),
        ])
        ->call('save')
        ->assertHasFormErrors(['custom_spp']);
});
