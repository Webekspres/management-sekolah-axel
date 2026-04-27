<?php

namespace App\Filament\Shared\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

/**
 * Shared Academic cluster used by Admin, Guru, and Kepsek panels.
 * Resources that need to appear in multiple panels reference this cluster.
 */
class AcademicCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $title = 'Akademik';
}
