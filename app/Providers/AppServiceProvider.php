<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Listeners\AuthActivityListener;
use App\Models\Announcement;
use App\Models\AttitudeScore;
use App\Models\Grade;
use App\Models\Kbm;
use App\Models\KnowledgeSkillScore;
use App\Models\LearningAchievement;
use App\Models\LessonPlan;
use App\Models\LessonPlanMaterial;
use App\Models\PersonalityScore;
use App\Models\Rapor;
use App\Models\SubjectKkm;
use App\Observers\AnnouncementObserver;
use App\Observers\KbmObserver;
use App\Observers\LessonPlanMaterialObserver;
use App\Observers\LessonPlanObserver;
use App\Observers\RaporObserver;
use App\Policies\AttitudeScorePolicy;
use App\Policies\GradePolicy;
use App\Policies\KnowledgeSkillScorePolicy;
use App\Policies\LearningAchievementPolicy;
use App\Policies\PersonalityScorePolicy;
use App\Policies\RaporPolicy;
use App\Policies\SubjectKkmPolicy;
use App\Support\ForeignKeyDeleteGuard;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    protected static ?string $heading = 'Homeschooling Tunas Karya Bangsa';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Filament\Auth\Http\Responses\Contracts\LoginResponse::class,
            LoginResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerGlobalDeletionValidation();
        $this->registerPolicies();
        $this->registerActivityLogListeners();
        $this->registerObservers();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function registerGlobalDeletionValidation(): void
    {
        Event::listen('eloquent.deleting: *', function (string $eventName, array $data): void {
            $model = $data[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }

            ForeignKeyDeleteGuard::ensureDeletable($model);
        });
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Grade::class, GradePolicy::class);
        Gate::policy(Rapor::class, RaporPolicy::class);
        Gate::policy(AttitudeScore::class, AttitudeScorePolicy::class);
        Gate::policy(KnowledgeSkillScore::class, KnowledgeSkillScorePolicy::class);
        Gate::policy(LearningAchievement::class, LearningAchievementPolicy::class);
        Gate::policy(PersonalityScore::class, PersonalityScorePolicy::class);
        Gate::policy(SubjectKkm::class, SubjectKkmPolicy::class);
    }

    protected function registerActivityLogListeners(): void
    {
        Event::listen(
            Login::class,
            [AuthActivityListener::class, 'handleLogin'],
        );

        Event::listen(
            Logout::class,
            [AuthActivityListener::class, 'handleLogout'],
        );
    }

    protected function registerObservers(): void
    {
        LessonPlan::observe(LessonPlanObserver::class);
        Kbm::observe(KbmObserver::class);
        Rapor::observe(RaporObserver::class);
        Announcement::observe(AnnouncementObserver::class);
        LessonPlanMaterial::observe(LessonPlanMaterialObserver::class);
    }
}
