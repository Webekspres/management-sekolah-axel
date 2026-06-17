<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuthActivityListener
{
    /** @var array<string, bool> */
    private static array $loggedUserIds = [];

    public function handleLogin(Login $event): void
    {
        $userId = $event->user->id;

        if (isset(self::$loggedUserIds['login_'.$userId])) {
            return;
        }

        self::$loggedUserIds['login_'.$userId] = true;

        try {
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'login',
                'entity_type' => User::class,
                'entity_id' => $userId,
                'log_name' => 'auth',
                'description' => 'User login: '.$event->user->name,
            ]);
        } catch (\Throwable) {
            logger()->warning('AuthActivityListener: failed to log login', ['user_id' => $userId]);
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        $userId = $event->user->id;

        if (isset(self::$loggedUserIds['logout_'.$userId])) {
            return;
        }

        self::$loggedUserIds['logout_'.$userId] = true;

        try {
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'logout',
                'entity_type' => User::class,
                'entity_id' => $userId,
                'log_name' => 'auth',
                'description' => 'User logout: '.$event->user->name,
            ]);
        } catch (\Throwable) {
            logger()->warning('AuthActivityListener: failed to log logout', ['user_id' => $userId]);
        }
    }
}
