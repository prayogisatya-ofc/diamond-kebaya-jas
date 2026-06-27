<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalPayment;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PdfExportController extends Controller
{
    public function transactions(Request $request): Response
    {
        $data = Rental::query()
            ->with('customer:id,name,whatsapp_number')
            ->when($request->query('date_from'), fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->query('date_to'), fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('payment_status'), fn ($query, string $status) => $query->where('payment_status', $status))
            ->when($request->query('customer_id'), fn ($query, string $customerId) => $query->where('customer_id', $customerId))
            ->latest()
            ->get();

        $pdf = Pdf::loadView('pdf.transactions', [
            'rentals' => $data,
            'store' => $this->store(),
            'title' => 'Laporan Transaksi',
            'filters' => $this->filterSummary($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-transaksi.pdf');
    }

    public function payments(Request $request): Response
    {
        $data = RentalPayment::query()
            ->with(['rental:id,invoice_number,customer_id', 'rental.customer:id,name,whatsapp_number', 'creator:id,name'])
            ->when($request->query('date_from'), fn ($query, string $date) => $query->whereDate('paid_at', '>=', $date))
            ->when($request->query('date_to'), fn ($query, string $date) => $query->whereDate('paid_at', '<=', $date))
            ->when($request->query('payment_type'), fn ($query, string $type) => $query->where('payment_type', $type))
            ->when($request->query('payment_method'), fn ($query, string $method) => $query->where('payment_method', $method))
            ->when($request->query('staff_id'), fn ($query, string $staffId) => $query->where('created_by', $staffId))
            ->latest('paid_at')
            ->get();

        $pdf = Pdf::loadView('pdf.payments', [
            'payments' => $data,
            'store' => $this->store(),
            'title' => 'Laporan Pembayaran',
            'filters' => $this->filterSummary($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-pembayaran.pdf');
    }

    public function rentedProducts(Request $request): Response
    {
        $data = RentalItem::query()
            ->with(['product:id,name,code', 'productVariant:id,name,sku'])
            ->whereHas('rental', function ($query) use ($request): void {
                $query
                    ->whereNotIn('status', ['cancelled'])
                    ->when($request->query('date_from'), fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
                    ->when($request->query('date_to'), fn ($query, string $date) => $query->whereDate('created_at', '<=', $date));
            })
            ->selectRaw('MIN(id) as id, product_id, product_variant_id, item_name_snapshot, variant_name_snapshot, SUM(quantity) as total_quantity, SUM(final_price) as total_revenue')
            ->groupBy('product_id', 'product_variant_id', 'item_name_snapshot', 'variant_name_snapshot')
            ->orderByDesc('total_quantity')
            ->get();

        $pdf = Pdf::loadView('pdf.rented-products', [
            'items' => $data,
            'store' => $this->store(),
            'title' => 'Laporan Produk Disewa',
            'filters' => $this->filterSummary($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-produk-disewa.pdf');
    }

    public function expenses(Request $request): Response
    {
        $data = Expense::query()
            ->with('creator:id,name')
            ->when($request->query('search'), fn ($query, string $search) => $query->where('description', 'like', "%{$search}%"))
            ->when($request->query('category'), fn ($query, string $category) => $query->where('category', $category))
            ->when($request->query('date_from'), fn ($query, string $date) => $query->whereDate('expense_date', '>=', $date))
            ->when($request->query('date_to'), fn ($query, string $date) => $query->whereDate('expense_date', '<=', $date))
            ->latest('expense_date')
            ->get();

        $totalAmount = $data->sum('amount');

        $pdf = Pdf::loadView('pdf.expenses', [
            'expenses' => $data,
            'totalAmount' => $totalAmount,
            'store' => $this->store(),
            'title' => 'Laporan Pengeluaran',
            'filters' => $this->filterSummary($request),
        ]);

        return $pdf->download('laporan-pengeluaran.pdf');
    }

    /**
     * @return array{name: string, address?: string, whatsapp_number?: string}
     */
    private function store(): array
    {
        $name = Setting::query()->where('key', 'store_name')->value('value');
        $address = Setting::query()->where('key', 'store_address')->value('value');
        $whatsapp = Setting::query()->where('key', 'store_whatsapp')->value('value');

        return [
            'name' => $name ?: 'Diamond Kebaya & Jas',
            'address' => $address ?: '',
            'whatsapp_number' => $whatsapp ?: '',
        ];
    }

    private function filterSummary(Request $request): string
    {
        $parts = [];

        if ($request->query('date_from') || $request->query('date_to')) {
            $parts[] = sprintf('Periode: %s - %s',
                $request->query('date_from') ?: '...',
                $request->query('date_to') ?: '...'
            );
        }

        if ($request->query('status')) {
            $parts[] = 'Status: '.$request->query('status');
        }

        if ($request->query('payment_status')) {
            $parts[] = 'Pembayaran: '.$request->query('payment_status');
        }

        if ($request->query('category')) {
            $parts[] = 'Kategori: '.$request->query('category');
        }

        if ($request->query('search')) {
            $parts[] = 'Cari: '.$request->query('search');
        }

        return implode(' | ', $parts);
    }
}
