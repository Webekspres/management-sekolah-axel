<?php

namespace App\Filament\Shared\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Shared Data Personalia cluster used by Admin and Kepsek panels.
 */
class DataPersonaliaCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $title = 'Data Personalia';
}
