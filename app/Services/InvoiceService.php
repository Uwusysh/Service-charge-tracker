<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function findDuplicate(Invoice $invoice): ?Invoice
    {
        return Invoice::query()
            ->where('building_id', $invoice->building_id)
            ->where('supplier_id', $invoice->supplier_id)
            ->where('normalized_reference', $invoice->normalized_reference)
            ->where('id', '!=', $invoice->id)
            ->whereNotIn('status', [Invoice::STATUS_REJECTED])
            ->first();
    }

    public function submit(Invoice $invoice, User $user): Invoice
    {
        if (! $invoice->budget || ! $invoice->budget->isFinal()) {
            throw ValidationException::withMessages([
                'budget_id' => 'Invoices can only be submitted against a Final budget.',
            ]);
        }

        if ($invoice->status !== Invoice::STATUS_DRAFT && $invoice->status !== Invoice::STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or rejected invoices can be submitted.',
            ]);
        }

        $duplicate = $this->findDuplicate($invoice);
        if ($duplicate) {
            throw ValidationException::withMessages([
                'invoice_reference' => sprintf(
                    'Duplicate invoice reference for this building and supplier (matches invoice #%d).',
                    $duplicate->id
                ),
            ]);
        }

        $invoice->update([
            'status' => Invoice::STATUS_SUBMITTED,
            'submitted_by' => $user->id,
            'submitted_at' => now(),
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ]);

        return $invoice->fresh();
    }

    public function approve(Invoice $invoice, User $user, ?string $note = null): Invoice
    {
        if (! $user->canApproveInvoice()) {
            throw ValidationException::withMessages([
                'status' => 'You are not permitted to approve invoices.',
            ]);
        }

        if ($invoice->status !== Invoice::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted invoices can be approved.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $user, $note) {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            $duplicate = $this->findDuplicate($locked);
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'invoice_reference' => sprintf(
                        'Duplicate invoice reference for this building and supplier (matches invoice #%d).',
                        $duplicate->id
                    ),
                ]);
            }

            $locked->update([
                'status' => Invoice::STATUS_APPROVED,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_note' => $note,
                'rejection_reason' => null,
                'reverse_reason' => null,
            ]);

            return $locked->fresh();
        });
    }

    public function reject(Invoice $invoice, User $user, string $reason): Invoice
    {
        if (! $user->canApproveInvoice()) {
            throw ValidationException::withMessages([
                'status' => 'You are not permitted to reject invoices.',
            ]);
        }

        if ($invoice->status !== Invoice::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted invoices can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $user, $reason) {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status' => Invoice::STATUS_REJECTED,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
                'review_note' => null,
            ]);

            return $locked->fresh();
        });
    }

    public function reverseApproval(Invoice $invoice, User $user, string $reason): Invoice
    {
        if (! $user->canApproveInvoice()) {
            throw ValidationException::withMessages([
                'status' => 'You are not permitted to reverse invoice approvals.',
            ]);
        }

        if ($invoice->status !== Invoice::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => 'Only approved invoices can have approval reversed.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $user, $reason) {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status' => Invoice::STATUS_SUBMITTED,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'reverse_reason' => $reason,
                'review_note' => null,
            ]);

            return $locked->fresh();
        });
    }

    public function calculateGross(string $net, string $vat): string
    {
        return Money::add($net, $vat);
    }
}
