<?php

namespace App\Http\Controllers;

use App\Http\Requests\PickUpRentalRequest;
use App\Http\Requests\ReturnRentalRequest;
use App\Models\Rental;
use App\Models\RentalPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RentalStatusController extends Controller
{
    public function pickUp(PickUpRentalRequest $request, Rental $rental): RedirectResponse
    {
        if ($rental->status !== 'booked') {
            return $this->invalidTransition('Barang hanya bisa ditandai diambil dari status booked.');
        }

        DB::transaction(function () use ($request, $rental): void {
            $validated = $request->validated();

            $rental->update([
                'status' => 'picked_up',
                'guarantee_type' => $validated['guarantee_type'] ?? $rental->guarantee_type,
                'picked_up_at' => now(),
                'picked_up_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Barang berhasil ditandai diambil.');
    }

    public function returnRental(ReturnRentalRequest $request, Rental $rental): RedirectResponse
    {
        if (! in_array($rental->status, ['picked_up', 'overdue'], true)) {
            return $this->invalidTransition('Barang hanya bisa dikembalikan dari status picked_up atau overdue.');
        }

        DB::transaction(function () use ($request, $rental): void {
            $validated = $request->validated();
            $returnedAt = Carbon::parse($validated['returned_at']);
            $penaltyAmount = (float) ($validated['penalty_amount'] ?? 0);

            $rental->update([
                'status' => 'returned',
                'returned_at' => $returnedAt,
                'returned_by' => $request->user()->id,
                'penalty_days' => $this->penaltyDays($rental, $returnedAt),
                'penalty_amount' => $penaltyAmount,
                'total_amount' => $this->totalAmountWithPenalty($rental, $penaltyAmount),
            ]);

            if ($request->boolean('pay_penalty_now') && $penaltyAmount > 0) {
                $rental->payments()->create([
                    'payment_type' => 'denda',
                    'payment_method' => $validated['penalty_payment_method'],
                    'amount' => $penaltyAmount,
                    'paid_at' => $validated['penalty_paid_at'],
                    'notes' => $validated['penalty_notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
            }

            $this->refreshPaymentTotals($rental);
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Barang berhasil ditandai dikembalikan.');
    }

    public function complete(Rental $rental): RedirectResponse
    {
        if ($rental->status !== 'returned') {
            return $this->invalidTransition('Rental hanya bisa diselesaikan setelah barang dikembalikan.');
        }

        if ($rental->payment_status !== 'paid' || (float) $rental->remaining_amount !== 0.0) {
            return $this->invalidTransition('Rental hanya bisa diselesaikan jika pembayaran sudah lunas.');
        }

        DB::transaction(function () use ($rental): void {
            $rental->update([
                'status' => 'completed',
            ]);
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Rental berhasil diselesaikan.');
    }

    public function cancel(Rental $rental): RedirectResponse
    {
        if ($rental->status !== 'booked') {
            return $this->invalidTransition('Rental hanya bisa dibatalkan dari status booked.');
        }

        DB::transaction(function () use ($rental): void {
            $rental->update([
                'status' => 'cancelled',
            ]);
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Rental berhasil dibatalkan.');
    }

    private function invalidTransition(string $message): RedirectResponse
    {
        return back()->withErrors([
            'status' => $message,
        ]);
    }

    private function penaltyDays(Rental $rental, Carbon $returnedAt): int
    {
        if ($returnedAt->lessThanOrEqualTo($rental->return_due_at)) {
            return 0;
        }

        return (int) ceil($rental->return_due_at->diffInSeconds($returnedAt) / 86400);
    }

    private function totalAmountWithPenalty(Rental $rental, float $penaltyAmount): float
    {
        return (float) $rental->subtotal_amount
            - (float) $rental->discount_amount
            + (float) $rental->custom_adjustment_amount
            + $penaltyAmount;
    }

    private function refreshPaymentTotals(Rental $rental): void
    {
        $rental->load('payments');

        $paidAmount = $rental->payments->sum(function (RentalPayment $payment): float {
            $amount = (float) $payment->amount;

            return $payment->payment_type === 'refund' ? -$amount : $amount;
        });
        $totalAmount = (float) $rental->total_amount;

        $rental->update([
            'paid_amount' => $paidAmount,
            'remaining_amount' => $totalAmount - $paidAmount,
            'payment_status' => $this->paymentStatus($totalAmount, $paidAmount),
        ]);
    }

    private function paymentStatus(float $totalAmount, float $paidAmount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount < $totalAmount) {
            return 'dp';
        }

        if ($paidAmount > $totalAmount) {
            return 'overpaid';
        }

        return 'paid';
    }
}
