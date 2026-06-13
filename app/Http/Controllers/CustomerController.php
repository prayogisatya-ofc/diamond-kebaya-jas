<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Rental;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $rentalsAvailable = $this->rentalsAvailable();

        $customers = Customer::query()
            ->when($rentalsAvailable, fn ($query) => $query->withCount('rentals'))
            ->when($rentalsAvailable && Schema::hasColumn('rentals', 'total_amount'), fn ($query) => $query->withSum('rentals as rentals_total_amount', 'total_amount'))
            ->when($rentalsAvailable && Schema::hasColumn('rentals', 'created_at'), fn ($query) => $query->withMax('rentals as last_transaction_at', 'created_at'))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('whatsapp_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Customer $customer): array => $this->customerPayload($customer));

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::query()->create($request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer berhasil ditambahkan.');
    }

    public function show(Customer $customer): Response
    {
        if ($this->rentalsAvailable()) {
            $customer->loadCount('rentals');
        }

        if ($this->rentalsAvailable() && Schema::hasColumn('rentals', 'total_amount')) {
            $customer->loadSum('rentals as rentals_total_amount', 'total_amount');
        }

        if ($this->rentalsAvailable() && Schema::hasColumn('rentals', 'created_at')) {
            $customer->loadMax('rentals as last_transaction_at', 'created_at');
        }

        return Inertia::render('Customers/Show', [
            'customer' => $this->customerPayload($customer),
            'rentalHistory' => $this->rentalHistory($customer),
            'hasRentalHistory' => $this->rentalsAvailable(),
        ]);
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customers/Edit', [
            'customer' => $this->customerPayload($customer),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($this->rentalsAvailable() && $customer->rentals()->exists()) {
            return back()->withErrors([
                'customer' => 'Customer sudah memiliki riwayat transaksi dan tidak bisa dihapus.',
            ]);
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer berhasil dihapus.');
    }

    /**
     * @return array{id: int, name: string, whatsapp_number: string, notes: string|null, rentals_count: int, rentals_total_amount: string, last_transaction_at: mixed}
     */
    private function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'whatsapp_number' => $customer->whatsapp_number,
            'notes' => $customer->notes,
            'rentals_count' => (int) ($customer->rentals_count ?? 0),
            'rentals_total_amount' => (string) ($customer->rentals_total_amount ?? '0.00'),
            'last_transaction_at' => $customer->last_transaction_at,
        ];
    }

    /**
     * @return array<int, array{id: int, invoice_number: string|null, status: string|null, payment_status: string|null, pickup_at: mixed, return_due_at: mixed, total_amount: string, paid_amount: string, remaining_amount: string, created_at: mixed}>
     */
    private function rentalHistory(Customer $customer): array
    {
        if (! $this->rentalsAvailable()) {
            return [];
        }

        return $customer->rentals()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Rental $rental): array => [
                'id' => $rental->id,
                'invoice_number' => $rental->getAttribute('invoice_number'),
                'status' => $rental->getAttribute('status'),
                'payment_status' => $rental->getAttribute('payment_status'),
                'pickup_at' => $rental->getAttribute('pickup_at'),
                'return_due_at' => $rental->getAttribute('return_due_at'),
                'total_amount' => (string) ($rental->getAttribute('total_amount') ?? '0.00'),
                'paid_amount' => (string) ($rental->getAttribute('paid_amount') ?? '0.00'),
                'remaining_amount' => (string) ($rental->getAttribute('remaining_amount') ?? '0.00'),
                'created_at' => $rental->created_at,
            ])
            ->all();
    }

    private function rentalsAvailable(): bool
    {
        return Schema::hasTable('rentals') && Schema::hasColumn('rentals', 'customer_id');
    }
}
