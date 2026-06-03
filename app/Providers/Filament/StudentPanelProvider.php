<?php

namespace App\Providers\Filament;

use App\Filament\Bento\BentoDashboard;
use App\Http\Middleware\EnsureStudentAcademicLevel;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StudentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('student')
            ->path('student')
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index');

        if (! app()->runningUnitTests()) {
            $panel = $panel->spa();
        }

        return $panel
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/theme.css')
            ->globalSearch(false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            ->discoverResources(in: app_path('Filament/Student/Resources'), for: 'App\Filament\Student\Resources')
            ->discoverPages(in: app_path('Filament/Student/Pages'), for: 'App\Filament\Student\Pages')
            ->pages([
                BentoDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Student/Widgets'), for: 'App\Filament\Student\Widgets')
            ->widgets([])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('@livewire(\'academic-level-switcher\')'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                EnsureStudentAcademicLevel::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
