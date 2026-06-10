<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Http\Responses\LoginResponse;
use App\Listeners\AuthActivityListener;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\AttitudeScore;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Kbm;
use App\Models\KnowledgeSkillScore;
use App\Models\LearningAchievement;
use App\Models\LessonPlan;
use App\Models\LessonPlanMaterial;
use App\Models\Payment;
use App\Models\PersonalityScore;
use App\Models\Rapor;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectKkm;
use App\Models\Teacher;
use App\Models\User;
use App\Observers\AnnouncementObserver;
use App\Observers\KbmObserver;
use App\Observers\LessonPlanMaterialObserver;
use App\Observers\LessonPlanObserver;
use App\Observers\RaporObserver;
use App\Policies\AcademicYearPolicy;
use App\Policies\ActivityLogPolicy;
use App\Policies\AnnouncementPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\AttitudeScorePolicy;
use App\Policies\GradePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\KbmPolicy;
use App\Policies\KnowledgeSkillScorePolicy;
use App\Policies\LearningAchievementPolicy;
use App\Policies\LessonPlanPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PersonalityScorePolicy;
use App\Policies\RaporPolicy;
use App\Policies\SchedulePolicy;
use App\Policies\SchoolClassPolicy;
use App\Policies\StaffPolicy;
use App\Policies\StudentPolicy;
use App\Policies\SubjectKkmPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TeacherPolicy;
use App\Services\PaymentGateways\LogPaymentGateway;
use App\Support\ForeignKeyDeleteGuard;
use Carbon\CarbonImmutable;
use Filament\Panel;
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

        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            $driver = config('payment.default_driver', 'log');
            $class = config("payment.drivers.{$driver}", LogPaymentGateway::class);

            return $this->app->make($class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureFilament();
        $this->configureDefaults();
        $this->registerGlobalDeletionValidation();
        $this->registerPolicies();
        $this->registerActivityLogListeners();
        $this->registerObservers();
    }

    protected function configureFilament(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->favicon(asset('favicon.png'));
        });
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
        Gate::policy(AcademicYear::class, AcademicYearPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(Grade::class, GradePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Kbm::class, KbmPolicy::class);
        Gate::policy(KnowledgeSkillScore::class, KnowledgeSkillScorePolicy::class);
        Gate::policy(LearningAchievement::class, LearningAchievementPolicy::class);
        Gate::policy(LessonPlan::class, LessonPlanPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(PersonalityScore::class, PersonalityScorePolicy::class);
        Gate::policy(Rapor::class, RaporPolicy::class);
        Gate::policy(Schedule::class, SchedulePolicy::class);
        Gate::policy(SchoolClass::class, SchoolClassPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Subject::class, SubjectPolicy::class);
        Gate::policy(SubjectKkm::class, SubjectKkmPolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(User::class, StaffPolicy::class);
        Gate::policy(AttitudeScore::class, AttitudeScorePolicy::class);
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
