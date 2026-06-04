<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class PaymentPolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Payment::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $payment)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Payment::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function update(User $user, Payment $payment): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $payment)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function delete(User $user, Payment $payment): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $payment)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }
}
