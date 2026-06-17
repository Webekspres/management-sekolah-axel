<?php

use App\Filament\Pages\TemporaryAccessManagement;
use App\Models\AccessPolicy;
use App\Models\User;
use App\Models\UserPolicyAbility;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function temporaryAccessLessonPlanPolicy(): AccessPolicy
{
    return AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();
}

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->admin = User::factory()->asAdmin()->create();
    actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

// ===========================================================================
// Requirement 1.3 — Konfirmasi modal dan submit
// ===========================================================================

test('klik action save dan konfirmasi mengeksekusi submit dan menyimpan data ke database', function () {
    $target = User::factory()->asSiswa()->create();
    $policy = temporaryAccessLessonPlanPolicy();

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$target->id])
        ->set("data.policy_abilities.{$policy->id}", ['viewAny'])
        ->set('data.duration', '1_week')
        ->callAction('save')
        ->assertNotified('Akses berhasil disimpan');

    expect(UserPolicyAbility::query()
        ->forUser($target->id)
        ->forPolicy($policy->id)
        ->forAbility('viewAny')
        ->direct()
        ->count()
    )->toBe(1);
});

test('klik action save dan batal tidak mengubah data di database', function () {
    $target = User::factory()->asSiswa()->create();
    $policy = temporaryAccessLessonPlanPolicy();

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$target->id])
        ->set("data.policy_abilities.{$policy->id}", ['viewAny'])
        ->set('data.duration', '1_week')
        ->mountAction('save')
        ->assertActionHalted('save');

    expect(UserPolicyAbility::query()
        ->forUser($target->id)
        ->forPolicy($policy->id)
        ->direct()
        ->count()
    )->toBe(0);
});
