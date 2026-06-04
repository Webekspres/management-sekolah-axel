<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class InvoicePolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Invoice::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $invoice)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Invoice::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $invoice)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $invoice)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
