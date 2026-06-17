<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AuthPanelProvider;
use App\Providers\Filament\GuruPanelProvider;
use App\Providers\Filament\KepsekPanelProvider;
use App\Providers\Filament\StudentPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AuthPanelProvider::class,
    GuruPanelProvider::class,
    KepsekPanelProvider::class,
    StudentPanelProvider::class,
];
