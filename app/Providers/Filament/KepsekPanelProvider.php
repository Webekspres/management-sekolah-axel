<?php

namespace App\Providers\Filament;

use App\Filament\Bento\BentoDashboard;
use App\Filament\Clusters\Academic\Resources\AcademicYears\AcademicYearResource;
use App\Filament\Clusters\Academic\Resources\Schedules\ScheduleResource;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Clusters\Academic\Resources\Subjects\SubjectResource;
use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Widgets\KepsekOverviewStats;
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
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class KepsekPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('kepsek')
            ->path('kepsek')
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index');

        if (! app()->runningUnitTests()) {
            $panel = $panel->spa();
        }

        return $panel
            ->colors([
                'primary' => Color::Amber,
            ])
            ->globalSearch(false)
            ->resources([
                AnnouncementResource::class,
                // Resource yang bisa di-assign via Akses Sementara.
                SchoolClassResource::class,
                AcademicYearResource::class,
                ScheduleResource::class,
                SubjectResource::class,
                TeacherResource::class,
                StudentResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Kepsek/Resources'), for: 'App\Filament\Kepsek\Resources')
            ->discoverPages(in: app_path('Filament/Kepsek/Pages'), for: 'App\Filament\Kepsek\Pages')
            ->pages([
                BentoDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Kepsek/Widgets'), for: 'App\Filament\Kepsek\Widgets')
            ->widgets([
                KepsekOverviewStats::class,
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('@livewire(\'academic-level-switcher\')'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('<style>
                    .fi-wi-widget{display:flex;flex-direction:column}
                    .fi-wi-widget>*{flex:1;display:flex;flex-direction:column}
                    .fi-wi-widget .fi-wi-table,.fi-wi-widget .fi-wi-stats-overview{flex:1}
                    .fi-wi-stats-overview .fi-sc-section{flex:1;display:flex;flex-direction:column}
                    .fi-wi-stats-overview .fi-sc-section>div:last-child{flex:1;display:grid}
                    .fi-wi-stats-overview .fi-wi-stats-overview-stat{height:100%}
                </style>'),
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
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
