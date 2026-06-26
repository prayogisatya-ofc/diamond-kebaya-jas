<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function transactions(Request $request): Response
    {
        $filters = [
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'status' => $request->query('status'),
            'payment_status' => $request->query('payment_status'),
            'customer_id' => $request->query('customer_id'),
        ];

        $rentals = Rental::query()
            ->with('customer:id,name,whatsapp_number')
            ->when($filters['date_from'], fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'], fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['status'], fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['payment_status'], fn ($query, string $status) => $query->where('payment_status', $status))
            ->when($filters['customer_id'], fn ($query, string $customerId) => $query->where('customer_id', $customerId))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Rental $rental): array => [
                'id' => $rental->id,
                'invoice_number' => $rental->invoice_number,
                'customer_name' => $rental->customer?->name,
                'created_at' => $rental->created_at,
                'pickup_at' => $rental->pickup_at,
                'return_due_at' => $rental->return_due_at,
                'status' => $rental->status,
                'payment_status' => $rental->payment_status,
                'total_amount' => $rental->total_amount,
                'paid_amount' => $rental->paid_amount,
                'remaining_amount' => $rental->remaining_amount,
                'penalty_amount' => $rental->penalty_amount,
            ]);

        return Inertia::render('Reports/Transactions', [
            'filters' => $filters,
            'rentals' => $rentals,
            'customers' => $this->customerOptions(),
            'statusOptions' => ['booked', 'picked_up', 'returned', 'completed', 'overdue', 'cancelled'],
            'paymentStatusOptions' => ['unpaid', 'dp', 'paid', 'overpaid'],
        ]);
    }

    public function payments(Request $request): Response
    {
        $filters = [
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'payment_type' => $request->query('payment_type'),
            'payment_method' => $request->query('payment_method'),
            'staff_id' => $request->query('staff_id'),
        ];

        $payments = RentalPayment::query()
            ->with(['rental:id,invoice_number,customer_id', 'rental.customer:id,name,whatsapp_number', 'creator:id,name'])
            ->when($filters['date_from'], fn ($query, string $date) => $query->whereDate('paid_at', '>=', $date))
            ->when($filters['date_to'], fn ($query, string $date) => $query->whereDate('paid_at', '<=', $date))
            ->when($filters['payment_type'], fn ($query, string $type) => $query->where('payment_type', $type))
            ->when($filters['payment_method'], fn ($query, string $method) => $query->where('payment_method', $method))
            ->when($filters['staff_id'], fn ($query, string $staffId) => $query->where('created_by', $staffId))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (RentalPayment $payment): array => [
                'id' => $payment->id,
                'paid_at' => $payment->paid_at,
                'invoice_number' => $payment->rental?->invoice_number,
                'rental_id' => $payment->rental_id,
                'customer_name' => $payment->rental?->customer?->name,
                'payment_type' => $payment->payment_type,
                'payment_method' => $payment->payment_method,
                'amount' => $payment->amount,
                'staff_name' => $payment->creator?->name,
                'notes' => $payment->notes,
            ]);

        return Inertia::render('Reports/Payments', [
            'filters' => $filters,
            'payments' => $payments,
            'staff' => $this->staffOptions(),
            'paymentTypeOptions' => ['dp', 'pelunasan', 'denda', 'refund', 'adjustment'],
            'paymentMethodOptions' => ['cash', 'transfer', 'qris', 'debit', 'other'],
        ]);
    }

    public function rentedProducts(Request $request): Response
    {
        $filters = [
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $items = RentalItem::query()
            ->with(['product:id,name,code', 'productVariant:id,name,sku'])
            ->whereHas('rental', function ($query) use ($filters): void {
                $query
                    ->whereNotIn('status', ['cancelled'])
                    ->when($filters['date_from'], fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
                    ->when($filters['date_to'], fn ($query, string $date) => $query->whereDate('created_at', '<=', $date));
            })
            ->selectRaw('MIN(id) as id, MAX(created_at) as latest_created_at, product_id, product_variant_id, item_name_snapshot, variant_name_snapshot, SUM(quantity) as total_quantity, SUM(final_price) as total_revenue')
            ->groupBy('product_id', 'product_variant_id', 'item_name_snapshot', 'variant_name_snapshot')
            ->orderByDesc('latest_created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (RentalItem $item): array => [
                'id' => $item->id,
                'product_name' => $item->product?->name ?? $item->item_name_snapshot,
                'product_code' => $item->product?->code,
                'variant_name' => $item->productVariant?->name ?? $item->variant_name_snapshot,
                'variant_sku' => $item->productVariant?->sku,
                'total_quantity' => (int) $item->total_quantity,
                'total_revenue' => (float) $item->total_revenue,
            ]);

        return Inertia::render('Reports/RentedProducts', [
            'filters' => $filters,
            'items' => $items,
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string, whatsapp_number: string}>
     */
    private function customerOptions(): array
    {
        return Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'whatsapp_number'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'whatsapp_number' => $customer->whatsapp_number,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function staffOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }
}
