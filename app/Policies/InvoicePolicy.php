<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
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

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $invoice)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Invoice::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $invoice)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $invoice)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::SuperAdmin)) {
            return false;
        }

        $status = $invoice->status instanceof PaymentStatus
            ? $invoice->status
            : PaymentStatus::tryFrom((string) $invoice->status);

        if (in_array($status, [PaymentStatus::Paid, PaymentStatus::Pending], true)) {
            return false;
        }

        if ($invoice->hasPaymentHistory()) {
            return false;
        }

        return true;
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function recordManual(User $user, Invoice $invoice): bool
    {
        if ($this->hasTemporaryAccess($user, 'recordManual', $invoice)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::SuperAdmin)) {
            return false;
        }

        $status = $invoice->status instanceof PaymentStatus
            ? $invoice->status
            : PaymentStatus::tryFrom((string) $invoice->status);

        return $status !== PaymentStatus::Paid;
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
