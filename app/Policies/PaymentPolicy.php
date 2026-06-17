<?php

namespace App\Policies;

use App\Enums\UserRole;
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

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $payment)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Payment::class)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SiswaOrtu) && $user->student !== null;
    }

    public function update(User $user, Payment $payment): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $payment)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function delete(User $user, Payment $payment): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $payment)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function verify(User $user, Payment $payment): bool
    {
        if ($this->hasTemporaryAccess($user, 'verify', $payment)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function reject(User $user, Payment $payment): bool
    {
        if ($this->hasTemporaryAccess($user, 'reject', $payment)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
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
