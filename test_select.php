<?php

use App\Models\Schedule;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$schedule = Schedule::first();
echo "Schedule ID: {$schedule->id}\n";
echo "Subject ID: {$schedule->subject_id}\n";
echo 'Subject Name: '.($schedule->subject->name ?? 'NULL')."\n";
