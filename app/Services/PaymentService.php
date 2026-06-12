<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Data\PaymentChargeResult;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private PaymentGateway $paymentGateway,
    ) {}

    public function canStudentPay(Invoice $invoice): bool
    {
        return $invoice->isPayableByStudent();
    }

    public function confirmOfflineTransfer(Invoice $invoice, Student $student): Payment
    {
        if (! $this->canStudentPay($invoice)) {
            throw new DomainException(__('pembayaran.notifications.cannot_pay'));
        }

        if ($invoice->student_id !== $student->id) {
            throw new DomainException(__('pembayaran.notifications.cannot_pay'));
        }

        return DB::transaction(function () use ($invoice): Payment {
            $invoice = $this->lockInvoiceForUpdate($invoice);
            $this->assertInvoicePayable($invoice);

            $this->cancelPendingPayments($invoice);

            $payment = Payment::query()->withoutInvoiceAcademicLevelScope()->create([
                'invoice_id' => $invoice->id,
                'amount_paid' => $invoice->amount,
                'payment_method' => PaymentMethod::Transfer->value,
                'status' => PaymentStatus::Pending,
                'paid_at' => null,
            ]);

            $this->updateInvoiceStatus($invoice, PaymentStatus::Pending);

            return $payment;
        });
    }

    /**
     * @return array{payment: Payment, result: PaymentChargeResult}
     */
    public function initiateOnlinePayment(Invoice $invoice, Student $student, PaymentMethod $method): array
    {
        if (! config('payment.student_gateway_enabled')) {
            throw new DomainException(__('pembayaran.notifications.gateway_disabled'));
        }

        if (! $this->canStudentPay($invoice)) {
            throw new DomainException(__('pembayaran.notifications.cannot_pay'));
        }

        if ($invoice->student_id !== $student->id) {
            throw new DomainException(__('pembayaran.notifications.cannot_pay'));
        }

        if (! $method->requiresGateway()) {
            throw new DomainException(__('pembayaran.notifications.cannot_pay'));
        }

        return DB::transaction(function () use ($invoice, $method): array {
            $invoice = $this->lockInvoiceForUpdate($invoice);
            $this->assertInvoicePayable($invoice);

            $this->cancelPendingPayments($invoice);

            $payment = Payment::query()->withoutInvoiceAcademicLevelScope()->create([
                'invoice_id' => $invoice->id,
                'amount_paid' => $invoice->amount,
                'payment_method' => $method->value,
                'status' => PaymentStatus::Pending,
                'paid_at' => null,
            ]);

            $this->updateInvoiceStatus($invoice, PaymentStatus::Pending);

            $result = $this->paymentGateway->createCharge($payment);

            if ($result->transactionId !== null) {
                $payment->update(['pg_transaction_id' => $result->transactionId]);
            }

            return ['payment' => $payment->fresh(), 'result' => $result];
        });
    }

    public function verifyPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $payment = Payment::query()
                ->withoutInvoiceAcademicLevelScope()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== PaymentStatus::Pending) {
                throw new DomainException(__('pembayaran.notifications.verify_pending_only'));
            }

            $invoice = $this->lockInvoiceForUpdate($payment->invoice_id);
            $this->assertInvoiceNotPaid($invoice);

            $this->cancelPendingPayments($invoice, exceptPaymentId: $payment->id);

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            $this->updateInvoiceStatus($invoice, PaymentStatus::Paid);

            return $payment->fresh();
        });
    }

    public function rejectPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $payment = Payment::query()
                ->withoutInvoiceAcademicLevelScope()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== PaymentStatus::Pending) {
                throw new DomainException(__('pembayaran.notifications.reject_pending_only'));
            }

            $invoice = $this->lockInvoiceForUpdate($payment->invoice_id);
            $this->assertInvoiceNotPaid($invoice);

            $payment->update([
                'status' => PaymentStatus::Failed,
                'paid_at' => null,
            ]);

            $this->updateInvoiceStatus($invoice, PaymentStatus::Failed);

            return $payment->fresh();
        });
    }

    public function recordManualPayment(
        Invoice $invoice,
        PaymentMethod $method,
        PaymentStatus $status = PaymentStatus::Paid,
    ): Payment {
        if (in_array($status, [PaymentStatus::Unpaid, PaymentStatus::Failed], true)) {
            throw new DomainException(__('pembayaran.notifications.invalid_manual_status'));
        }

        return DB::transaction(function () use ($invoice, $method, $status): Payment {
            $invoice = $this->lockInvoiceForUpdate($invoice);
            $this->assertInvoiceNotPaid($invoice);
            $this->assertNoPaidPaymentExists($invoice);

            $this->cancelPendingPayments($invoice);

            $payment = Payment::query()->withoutInvoiceAcademicLevelScope()->create([
                'invoice_id' => $invoice->id,
                'amount_paid' => $invoice->amount,
                'payment_method' => $method->value,
                'status' => $status,
                'paid_at' => $status === PaymentStatus::Paid ? now() : null,
            ]);

            $this->updateInvoiceStatus($invoice, $status);

            return $payment;
        });
    }

    protected function lockInvoiceForUpdate(Invoice|string $invoice): Invoice
    {
        $invoiceId = $invoice instanceof Invoice ? $invoice->id : $invoice;

        return Invoice::query()
            ->withoutGlobalScopes()
            ->whereKey($invoiceId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function assertInvoicePayable(Invoice $invoice): void
    {
        if (! $invoice->isPayableByStudent()) {
            throw new DomainException(__('pembayaran.notifications.cannot_pay'));
        }
    }

    protected function assertInvoiceNotPaid(Invoice $invoice): void
    {
        $status = $invoice->status instanceof PaymentStatus
            ? $invoice->status
            : PaymentStatus::tryFrom((string) $invoice->status);

        if ($status === PaymentStatus::Paid) {
            throw new DomainException(__('pembayaran.notifications.invoice_already_paid'));
        }
    }

    protected function assertNoPaidPaymentExists(Invoice $invoice): void
    {
        $hasPaid = Payment::query()
            ->withoutInvoiceAcademicLevelScope()
            ->where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Paid)
            ->exists();

        if ($hasPaid) {
            throw new DomainException(__('pembayaran.notifications.invoice_already_paid'));
        }
    }

    protected function updateInvoiceStatus(Invoice|string $invoice, PaymentStatus $status): void
    {
        $invoiceId = $invoice instanceof Invoice ? $invoice->id : $invoice;

        Invoice::query()
            ->withoutGlobalScopes()
            ->whereKey($invoiceId)
            ->update(['status' => $status->value]);

        if ($invoice instanceof Invoice) {
            $invoice->setAttribute('status', $status);
            $invoice->syncOriginalAttribute('status');
        }
    }

    protected function cancelPendingPayments(Invoice $invoice, ?string $exceptPaymentId = null): void
    {
        Payment::query()
            ->withoutInvoiceAcademicLevelScope()
            ->where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Pending)
            ->when($exceptPaymentId !== null, fn ($query) => $query->where('id', '!=', $exceptPaymentId))
            ->update(['status' => PaymentStatus::Failed]);
    }
}
