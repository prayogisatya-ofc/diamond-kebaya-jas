<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalPayment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RentalInvoiceController extends Controller
{
    public function __invoke(Request $request, Rental $rental): Response
    {
        $rental->load([
            'customer:id,name,whatsapp_number,notes',
            'creator:id,name',
            'pickedUpBy:id,name',
            'returnedBy:id,name',
            'items.rentalPackage:id,name',
            'payments.creator:id,name',
        ]);

        return Inertia::render('Rentals/Invoice', [
            'publicView' => $request->routeIs('public.rentals.invoice'),
            'store' => $this->storePayload(),
            'rental' => $this->rentalPayload($rental),
            'items' => $rental->items
                ->sortBy('id')
                ->values()
                ->map(fn (RentalItem $item): array => $this->itemPayload($item))
                ->all(),
            'payments' => $rental->payments
                ->sortBy('paid_at')
                ->values()
                ->map(fn (RentalPayment $payment): array => $this->paymentPayload($payment))
                ->all(),
        ]);
    }

    /**
     * @return array{name: string, address: string, whatsapp_number: string, logo_url: string|null, footer_note: string, primary_color: string}
     */
    private function storePayload(): array
    {
        $profile = Setting::storeProfile();

        return [
            'name' => $profile['store_name'],
            'address' => $profile['store_address'],
            'whatsapp_number' => $profile['store_whatsapp_number'],
            'logo_url' => $profile['store_logo_url'],
            'footer_note' => $profile['invoice_footer_note'],
            'primary_color' => $profile['primary_color'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rentalPayload(Rental $rental): array
    {
        return [
            'id' => $rental->id,
            'invoice_number' => $rental->invoice_number,
            'customer' => $rental->customer ? [
                'name' => $rental->customer->name,
                'whatsapp_number' => $rental->customer->whatsapp_number,
            ] : null,
            'status' => $rental->status,
            'payment_status' => $rental->payment_status,
            'guarantee_type' => $rental->guarantee_type,
            'pickup_at' => $rental->pickup_at,
            'return_due_at' => $rental->return_due_at,
            'picked_up_at' => $rental->picked_up_at,
            'returned_at' => $rental->returned_at,
            'subtotal_amount' => $rental->subtotal_amount,
            'discount_amount' => $rental->discount_amount,
            'custom_adjustment_amount' => $rental->custom_adjustment_amount,
            'penalty_days' => $rental->penalty_days,
            'penalty_amount' => $rental->penalty_amount,
            'total_amount' => $rental->total_amount,
            'paid_amount' => $rental->paid_amount,
            'remaining_amount' => $rental->remaining_amount,
            'notes' => $rental->notes,
            'created_at' => $rental->created_at,
            'creator' => $rental->creator ? [
                'name' => $rental->creator->name,
            ] : null,
            'picked_up_by' => $rental->pickedUpBy ? [
                'name' => $rental->pickedUpBy->name,
            ] : null,
            'returned_by' => $rental->returnedBy ? [
                'name' => $rental->returnedBy->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemPayload(RentalItem $item): array
    {
        return [
            'id' => $item->id,
            'package_name' => $item->rentalPackage?->name,
            'item_name_snapshot' => $item->item_name_snapshot,
            'variant_name_snapshot' => $item->variant_name_snapshot,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'discount_amount' => $item->discount_amount,
            'final_price' => $item->final_price,
            'notes' => $item->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(RentalPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_type' => $payment->payment_type,
            'payment_method' => $payment->payment_method,
            'amount' => $payment->amount,
            'paid_at' => $payment->paid_at,
            'notes' => $payment->notes,
            'creator' => $payment->creator ? [
                'name' => $payment->creator->name,
            ] : null,
        ];
    }
}
