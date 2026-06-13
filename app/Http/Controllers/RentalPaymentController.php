<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRentalPaymentRequest;
use App\Models\Rental;
use App\Models\RentalPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RentalPaymentController extends Controller
{
    public function store(StoreRentalPaymentRequest $request, Rental $rental): RedirectResponse
    {
        DB::transaction(function () use ($request, $rental): void {
            $validated = $request->validated();

            $rental->payments()->create([
                'payment_type' => $validated['payment_type'],
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'paid_at' => $validated['paid_at'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $this->refreshPaymentTotals($rental);
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Pembayaran berhasil ditambahkan.');
    }

    public function destroy(Rental $rental, RentalPayment $rentalPayment): RedirectResponse
    {
        if ($rentalPayment->rental_id !== $rental->id) {
            abort(404);
        }

        if ($rental->status === 'completed') {
            return back()->withErrors([
                'payments' => 'Pembayaran tidak bisa dihapus saat rental sudah selesai.',
            ]);
        }

        DB::transaction(function () use ($rental, $rentalPayment): void {
            $rentalPayment->delete();
            $this->refreshPaymentTotals($rental);
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Pembayaran berhasil dihapus.');
    }

    private function refreshPaymentTotals(Rental $rental): void
    {
        $rental->load('payments');

        $paidAmount = $rental->payments->sum(function ($payment): float {
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
