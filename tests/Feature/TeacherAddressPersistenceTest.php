<?php

use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages\CreateTeacher;
use App\Models\City;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\User;
use App\Models\Village;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->asAdmin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('admin dapat menyimpan alamat guru saat membuat akun', function () {
    $province = Province::factory()->create();
    $city = City::factory()->create(['province_id' => $province->id]);
    $subDistrict = SubDistrict::factory()->create(['city_id' => $city->id]);
    $village = Village::factory()->create(['sub_district_id' => $subDistrict->id]);

    $email = 'guru.alamat@example.test';

    Livewire::test(CreateTeacher::class)
        ->fillForm([
            'user.name' => 'Guru Alamat',
            'user.email' => $email,
            'user.password' => 'password-1234',
            'user.gender' => 'L',
            'address_province_id' => $province->id,
            'address_city_id' => $city->id,
            'address_sub_district_id' => $subDistrict->id,
            'address_village_id' => $village->id,
            'address_street' => 'Jl. Mawar No. 10',
            'address_postal_code' => '12345',
        ])
        ->call('create')
        ->assertHasNoErrors();

    $user = User::query()->where('email', $email)->first();

    expect($user)->not->toBeNull()
        ->and($user->address_id)->not->toBeNull()
        ->and($user->address?->province_id)->toBe($province->id)
        ->and($user->address?->city_id)->toBe($city->id)
        ->and($user->address?->sub_district_id)->toBe($subDistrict->id)
        ->and($user->address?->village_id)->toBe($village->id)
        ->and($user->address?->street)->toBe('Jl. Mawar No. 10')
        ->and($user->address?->postal_code)->toBe('12345')
        ->and($user->teacher)->not->toBeNull();
});
