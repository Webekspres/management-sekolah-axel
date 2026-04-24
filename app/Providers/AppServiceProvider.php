<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Support\ForeignKeyDeleteGuard;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
}
