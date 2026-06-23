<?php

use App\Filament\Clusters\Keuangan\Resources\Invoices\Pages\ListInvoices;
use App\Models\Setting;
use App\Models\User;
use App\Services\SchoolPaymentSettingsService;
use App\Support\SchoolPaymentSettings;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('updates payment settings from form data', function () {
    app(SchoolPaymentSettingsService::class)->updateFromFormData([
        'bank_name' => 'Bank Mandiri',
        'account_number' => '9876543210',
        'account_holder' => 'Yayasan Sekolah',
        'school_whatsapp' => '08111222333',
    ]);

    expect(SchoolPaymentSettings::get(SchoolPaymentSettings::BANK_NAME))->toBe('Bank Mandiri')
        ->and(SchoolPaymentSettings::get(SchoolPaymentSettings::ACCOUNT_NUMBER))->toBe('9876543210')
        ->and(SchoolPaymentSettings::get(SchoolPaymentSettings::ACCOUNT_HOLDER))->toBe('Yayasan Sekolah')
        ->and(SchoolPaymentSettings::get(SchoolPaymentSettings::SCHOOL_WHATSAPP))->toBe('08111222333');
});

it('admin can save payment settings from invoice list slideover', function () {
    Setting::factory()->create([
        'key' => SchoolPaymentSettings::BANK_NAME,
        'value' => 'Bank BCA',
    ]);
    Setting::factory()->create([
        'key' => SchoolPaymentSettings::ACCOUNT_NUMBER,
        'value' => '1234567890',
    ]);
    Setting::factory()->create([
        'key' => SchoolPaymentSettings::ACCOUNT_HOLDER,
        'value' => 'Yayasan Lama',
    ]);
    Setting::factory()->create([
        'key' => SchoolPaymentSettings::SCHOOL_WHATSAPP,
        'value' => '081234567890',
    ]);

    $admin = User::factory()->asAdmin()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($admin);

    Livewire::test(ListInvoices::class)
        ->callAction('manage_payment_settings', data: [
            'bank_name' => 'Bank BNI',
            'account_number' => '5555666677',
            'account_holder' => 'Yayasan Baru',
            'school_whatsapp' => '08999888777',
        ])
        ->assertNotified();

    expect(SchoolPaymentSettings::get(SchoolPaymentSettings::BANK_NAME))->toBe('Bank BNI')
        ->and(SchoolPaymentSettings::get(SchoolPaymentSettings::ACCOUNT_NUMBER))->toBe('5555666677')
        ->and(SchoolPaymentSettings::get(SchoolPaymentSettings::ACCOUNT_HOLDER))->toBe('Yayasan Baru')
        ->and(SchoolPaymentSettings::get(SchoolPaymentSettings::SCHOOL_WHATSAPP))->toBe('08999888777');
});

it('rejects non numeric account number in payment settings slideover', function () {
    $admin = User::factory()->asAdmin()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($admin);

    Livewire::test(ListInvoices::class)
        ->callAction('manage_payment_settings', data: [
            'bank_name' => 'Bank BCA',
            'account_number' => 'ABC-123',
            'account_holder' => 'Yayasan',
            'school_whatsapp' => '081234567890',
        ])
        ->assertHasActionErrors(['account_number' => 'regex']);
});

it('guru cannot access payment settings action on invoice list', function () {
    $guru = User::factory()->asGuru()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($guru)
        ->get(ListInvoices::getUrl())
        ->assertForbidden();
});
