<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRentalRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalPackage;
use App\Models\RentalPackageItem;
use App\Models\RentalPayment;
use App\Models\Setting;
use App\Services\FonnteWhatsappService;
use App\Services\RentalAvailabilityService;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RentalController extends Controller
{
    public function __construct(private readonly FonnteWhatsappService $whatsappService) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->trim()->toString(),
            'payment_status' => $request->string('payment_status')->trim()->toString(),
            'pickup_from' => $request->string('pickup_from')->trim()->toString(),
            'pickup_to' => $request->string('pickup_to')->trim()->toString(),
        ];

        $rentals = Rental::query()
            ->with('customer:id,name,whatsapp_number')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query->where('invoice_number', 'like', "%{$filters['search']}%")
                        ->orWhereHas('customer', function ($query) use ($filters): void {
                            $query->where('name', 'like', "%{$filters['search']}%")
                                ->orWhere('whatsapp_number', 'like', "%{$filters['search']}%");
                        });
                });
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['payment_status'] !== '', fn ($query) => $query->where('payment_status', $filters['payment_status']))
            ->when($filters['pickup_from'] !== '', fn ($query) => $query->whereDate('pickup_at', '>=', $filters['pickup_from']))
            ->when($filters['pickup_to'] !== '', fn ($query) => $query->whereDate('pickup_at', '<=', $filters['pickup_to']))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Rental $rental): array => $this->rentalPayload($rental));

        return Inertia::render('Rentals/Index', [
            'rentals' => $rentals,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Rentals/Create', [
            'customers' => $this->customerOptions(),
            'products' => $this->productOptions(),
            'packages' => $this->packageOptions(),
        ]);
    }

    public function store(StoreRentalRequest $request): RedirectResponse
    {
        $rental = DB::transaction(function () use ($request): Rental {
            $validated = $request->validated();
            $customer = $this->resolveCustomer($validated);
            $items = collect($validated['items'])
                ->map(fn (array $item): array => $this->itemAttributes($item))
                ->values();
            $subtotalAmount = $items->sum('final_price');
            $totalAmount = (float) ($validated['custom_total_amount'] ?? $subtotalAmount);
            $paidAmount = (float) ($validated['initial_payment_amount'] ?? 0);

            $rental = Rental::query()->create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'customer_id' => $customer->id,
                'status' => 'booked',
                'payment_status' => $this->paymentStatus($totalAmount, $paidAmount),
                'guarantee_type' => $validated['guarantee_type'] ?? null,
                'pickup_at' => $validated['pickup_at'],
                'return_due_at' => $validated['return_due_at'],
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => 0,
                'custom_adjustment_amount' => $totalAmount - $subtotalAmount,
                'penalty_days' => 0,
                'penalty_amount' => 0,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $totalAmount - $paidAmount,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $rental->items()->createMany($items->all());

            if ($paidAmount > 0) {
                $rental->payments()->create([
                    'payment_type' => $paidAmount >= $totalAmount ? 'pelunasan' : 'dp',
                    'payment_method' => $validated['initial_payment_method'],
                    'amount' => $paidAmount,
                    'paid_at' => now(),
                    'notes' => $validated['initial_payment_notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
            }

            return $rental;
        });

        $this->whatsappService->sendRentalCreated($rental);

        return redirect()->route('rentals.show', $rental)->with('success', 'Rental berhasil dibuat.');
    }

    public function show(Rental $rental): Response
    {
        $rental->load([
            'customer:id,name,whatsapp_number,notes',
            'creator:id,name',
            'pickedUpBy:id,name',
            'returnedBy:id,name',
            'items.product:id,name,code,image_path,base_rental_price',
            'items.productVariant:id,product_id,name,sku,size,color,rental_price',
            'items.rentalPackage:id,name',
            'payments.creator:id,name',
        ]);

        return Inertia::render('Rentals/Show', [
            'rental' => $this->rentalPayload($rental),
            'items' => $rental->items
                ->sortBy('id')
                ->values()
                ->map(fn (RentalItem $item): array => $this->rentalItemPayload($item)),
            'payments' => $rental->payments
                ->sortByDesc('paid_at')
                ->values()
                ->map(fn (RentalPayment $payment): array => $this->paymentPayload($payment)),
            'products' => $this->productOptions($rental->pickup_at, $rental->return_due_at),
            'store' => $this->storePayload(),
        ]);
    }

    public function destroy(Request $request, Rental $rental): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);

        $invoiceNumber = $rental->invoice_number;

        $rental->delete();

        return redirect()
            ->route('rentals.index')
            ->with('success', "Rental {$invoiceNumber} berhasil dihapus.");
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
     * @param  array<string, mixed>  $validated
     */
    private function resolveCustomer(array $validated): Customer
    {
        if ($validated['customer_mode'] === 'existing') {
            return Customer::query()->findOrFail($validated['customer_id']);
        }

        return Customer::query()->create([
            'name' => $validated['new_customer']['name'],
            'whatsapp_number' => $validated['new_customer']['whatsapp_number'],
            'notes' => $validated['new_customer']['notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function itemAttributes(array $item): array
    {
        $product = Product::query()->findOrFail($item['product_id']);
        $variant = filled($item['product_variant_id'] ?? null)
            ? ProductVariant::query()->findOrFail($item['product_variant_id'])
            : null;
        $quantity = (int) $item['quantity'];
        $unitPrice = (float) $item['unit_price'];
        $discountAmount = (float) ($item['discount_amount'] ?? 0);

        return [
            'rental_package_id' => $item['rental_package_id'] ?? null,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'item_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant?->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discountAmount,
            'final_price' => max(0, ($quantity * $unitPrice) - $discountAmount),
            'notes' => $item['notes'] ?? null,
        ];
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $latestInvoice = Rental::query()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->latest('id')
            ->value('invoice_number');

        $sequence = $latestInvoice ? ((int) str($latestInvoice)->afterLast('-')->toString()) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
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
     * @return array<int, array{id: int, name: string, code: string|null, image_url: string|null, base_rental_price: string, variants: array<int, array{id: int, name: string, sku: string|null, size: string|null, color: string|null, image_url: string|null, stock_quantity: int, rental_price: string|null, available_quantity: int|null}>}>
     */
    private function productOptions(CarbonInterface|string|null $pickupAt = null, CarbonInterface|string|null $returnDueAt = null, ?int $ignoreRentalId = null): array
    {
        $availabilityService = app(RentalAvailabilityService::class);

        return Product::query()
            ->with('variants:id,product_id,name,sku,size,color,image_path,stock_quantity,rental_price')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'image_path', 'base_rental_price'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'image_url' => $product->imageUrl(),
                'base_rental_price' => $product->base_rental_price,
                'variants' => $product->variants
                    ->sortBy('name')
                    ->values()
                    ->map(function (ProductVariant $variant) use ($availabilityService, $pickupAt, $returnDueAt, $ignoreRentalId): array {
                        return [
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'sku' => $variant->sku,
                            'size' => $variant->size,
                            'color' => $variant->color,
                            'image_url' => $variant->imageUrl(),
                            'stock_quantity' => $variant->stock_quantity,
                            'rental_price' => $variant->rental_price,
                            'available_quantity' => ($pickupAt && $returnDueAt)
                                ? $availabilityService->availabilityForVariant($variant, $pickupAt, $returnDueAt, $ignoreRentalId)['available_quantity']
                                : null,
                        ];
                    })
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, package_price: string, items: array<int, array{rental_package_id: int, product_id: int, product_variant_id: int|null, quantity: int, unit_price: string, discount_amount: int, notes: string|null}>}>
     */
    private function packageOptions(): array
    {
        return RentalPackage::query()
            ->with([
                'items.product:id,name,base_rental_price',
                'items.productVariant:id,product_id,name,rental_price',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'package_price'])
            ->map(fn (RentalPackage $rentalPackage): array => [
                'id' => $rentalPackage->id,
                'name' => $rentalPackage->name,
                'package_price' => $rentalPackage->package_price,
                'items' => $rentalPackage->items
                    ->sortBy('id')
                    ->values()
                    ->map(fn (RentalPackageItem $item): array => [
                        'rental_package_id' => $rentalPackage->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->default_item_price
                            ?? $item->productVariant?->rental_price
                            ?? $item->product->base_rental_price,
                        'discount_amount' => 0,
                        'notes' => $item->notes,
                    ])
                    ->all(),
            ])
            ->all();
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
                'id' => $rental->customer->id,
                'name' => $rental->customer->name,
                'whatsapp_number' => $rental->customer->whatsapp_number,
                'notes' => $rental->customer->notes,
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
            'creator' => $rental->creator ? [
                'id' => $rental->creator->id,
                'name' => $rental->creator->name,
            ] : null,
            'picked_up_by' => $rental->pickedUpBy ? [
                'id' => $rental->pickedUpBy->id,
                'name' => $rental->pickedUpBy->name,
            ] : null,
            'returned_by' => $rental->returnedBy ? [
                'id' => $rental->returnedBy->id,
                'name' => $rental->returnedBy->name,
            ] : null,
            'actions' => [
                'can_pick_up' => $rental->status === 'booked',
                'can_return' => in_array($rental->status, ['picked_up', 'overdue'], true),
                'can_complete' => $rental->status === 'returned' && $rental->payment_status === 'paid' && (float) $rental->remaining_amount === 0.0,
                'can_cancel' => $rental->status === 'booked',
                'can_delete' => request()->user()?->isOwner() === true,
            ],
            'created_at' => $rental->created_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rentalItemPayload(RentalItem $item): array
    {
        return [
            'id' => $item->id,
            'rental_package' => $item->rentalPackage ? [
                'id' => $item->rentalPackage->id,
                'name' => $item->rentalPackage->name,
            ] : null,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
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
                'id' => $payment->creator->id,
                'name' => $payment->creator->name,
            ] : null,
        ];
    }
}
