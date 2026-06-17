<?php

use App\Filament\Guru\Resources\LessonPlans\LessonPlanResource;
use App\Filament\Pages\TemporaryAccessManagement;
use App\Models\AccessPolicy;
use App\Models\LessonPlan;
use App\Models\User;
use App\Models\UserPolicyAbility;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('admin assigns temporary access and target user can use guru lesson plans', function (): void {
    $admin = User::factory()->asAdmin()->create();
    $target = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();

    expect($target->canAccessPanel(Filament::getPanel('guru')))->toBeFalse();
    expect(Gate::forUser($target)->allows('viewAny', LessonPlan::class))->toBeFalse();

    $this->actingAs($target)
        ->get('/guru/lesson-plans')
        ->assertForbidden();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    actingAs($admin);

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

    $target->refresh();

    expect($target->canAccessPanel(Filament::getPanel('guru')))->toBeTrue();
    expect(Gate::forUser($target)->allows('viewAny', LessonPlan::class))->toBeTrue();

    Filament::setCurrentPanel(Filament::getPanel('guru'));
    actingAs($target);

    expect(LessonPlanResource::canAccess())->toBeTrue();

    $this->actingAs($target)
        ->get('/guru/lesson-plans')
        ->assertOk();
});
