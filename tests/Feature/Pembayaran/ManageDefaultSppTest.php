<?php

use App\Filament\Clusters\Keuangan\Resources\Invoices\Pages\ListInvoices;
use App\Models\Level;
use App\Models\User;
use App\Services\LevelDefaultSppService;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('updates default_spp per level from form data', function () {
    $sd = Level::factory()->create(['name' => 'SD', 'default_spp' => 150000]);
    $smp = Level::factory()->create(['name' => 'SMP', 'default_spp' => 250000]);

    app(LevelDefaultSppService::class)->updateFromFormData([
        $sd->id => 175000,
        $smp->id => 275000,
        'invalid-id' => 999999,
    ]);

    expect((float) $sd->fresh()->default_spp)->toBe(175000.0)
        ->and((float) $smp->fresh()->default_spp)->toBe(275000.0);
});

it('admin can save default spp from invoice list slideover', function () {
    $sd = Level::factory()->create(['name' => 'SD', 'default_spp' => 150000]);
    $smp = Level::factory()->create(['name' => 'SMP', 'default_spp' => 250000]);

    $admin = User::factory()->asAdmin()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($admin);

    Livewire::test(ListInvoices::class)
        ->callAction('manage_default_spp', data: [
            'levels' => [
                $sd->id => 180000,
                $smp->id => 290000,
            ],
        ])
        ->assertNotified();

    expect((float) $sd->fresh()->default_spp)->toBe(180000.0)
        ->and((float) $smp->fresh()->default_spp)->toBe(290000.0);
});

it('admin can save one and a half million from masked input', function () {
    $sd = Level::factory()->create(['name' => 'SD', 'default_spp' => 150000]);

    $admin = User::factory()->asAdmin()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($admin);

    Livewire::test(ListInvoices::class)
        ->callAction('manage_default_spp', data: [
            'levels' => [
                $sd->id => '1.500.000',
            ],
        ])
        ->assertNotified();

    expect((float) $sd->fresh()->default_spp)->toBe(1500000.0);
});

it('guru cannot access invoice list with spp settings', function () {
    $guru = User::factory()->asGuru()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($guru)
        ->get(ListInvoices::getUrl())
        ->assertForbidden();
});
