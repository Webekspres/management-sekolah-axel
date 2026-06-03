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
        $invoice = $this->resolveInvoice($invoice);

        if (! $this->canStudentPay($invoice)) {
            throw new DomainException(__('pembayaran.notifications.cannot_pay'));
        }

        if ($invoice->student_id !== $student->id) {
            throw new DomainException(__('pembayaran.notifications.cannot_pay'));
        }

        return DB::transaction(function () use ($invoice): Payment {
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
        $invoice = $this->resolveInvoice($invoice);

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
        if ($payment->status !== PaymentStatus::Pending) {
            throw new DomainException('Hanya pembayaran menunggu verifikasi yang dapat disetujui.');
        }

        return DB::transaction(function () use ($payment): Payment {
            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            $this->updateInvoiceStatus($payment->invoice_id, PaymentStatus::Paid);

            return $payment->fresh();
        });
    }

    public function rejectPayment(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw new DomainException('Hanya pembayaran menunggu verifikasi yang dapat ditolak.');
        }

        return DB::transaction(function () use ($payment): Payment {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'paid_at' => null,
            ]);

            $this->updateInvoiceStatus($payment->invoice_id, PaymentStatus::Failed);

            return $payment->fresh();
        });
    }

    public function recordManualPayment(
        Invoice $invoice,
        PaymentMethod $method,
        PaymentStatus $status = PaymentStatus::Paid,
    ): Payment {
        return DB::transaction(function () use ($invoice, $method, $status): Payment {
            if ($status === PaymentStatus::Pending) {
                $this->cancelPendingPayments($invoice);
            }

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

    protected function resolveInvoice(Invoice $invoice): Invoice
    {
        return Invoice::query()
            ->withoutGlobalScopes()
            ->whereKey($invoice->id)
            ->firstOrFail();
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

    protected function cancelPendingPayments(Invoice $invoice): void
    {
        Payment::query()
            ->withoutInvoiceAcademicLevelScope()
            ->where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Pending)
            ->update(['status' => PaymentStatus::Failed]);
    }
}
