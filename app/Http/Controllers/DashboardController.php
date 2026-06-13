<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\RentalPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = now();
        $startOfDay = $today->copy()->startOfDay();
        $endOfDay = $today->copy()->endOfDay();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        return Inertia::render('Dashboard', [
            'summary' => [
                'revenue_today' => $this->paymentTotal($startOfDay, $endOfDay),
                'revenue_month' => $this->paymentTotal($startOfMonth, $endOfMonth),
                'dp_total' => $this->paymentTotal($startOfMonth, $endOfMonth, ['dp']),
                'pelunasan_total' => $this->paymentTotal($startOfMonth, $endOfMonth, ['pelunasan']),
                'penalty_total' => $this->paymentTotal($startOfMonth, $endOfMonth, ['denda']),
                'outstanding_total' => (float) Rental::query()
                    ->whereNotIn('status', ['cancelled'])
                    ->where('remaining_amount', '>', 0)
                    ->sum('remaining_amount'),
                'active_transactions' => Rental::query()
                    ->whereIn('status', ['booked', 'picked_up', 'overdue'])
                    ->count(),
                'pickup_today_count' => $this->pickupTodayQuery($startOfDay, $endOfDay)->count(),
                'return_today_count' => $this->returnTodayQuery($startOfDay, $endOfDay)->count(),
                'overdue_count' => $this->overdueQuery($today)->count(),
            ],
            'pickupToday' => $this->rentalList($this->pickupTodayQuery($startOfDay, $endOfDay)->oldest('pickup_at')->limit(6)->get()),
            'returnToday' => $this->rentalList($this->returnTodayQuery($startOfDay, $endOfDay)->oldest('return_due_at')->limit(6)->get()),
            'overdueRentals' => $this->rentalList($this->overdueQuery($today)->oldest('return_due_at')->limit(6)->get()),
            'recentRentals' => $this->rentalList(Rental::query()
                ->with('customer:id,name,whatsapp_number')
                ->latest()
                ->limit(8)
                ->get()),
            'dailyRevenue' => $this->dailyRevenue(),
        ]);
    }

    /**
     * @param  array<int, string>|null  $types
     */
    private function paymentTotal(Carbon $start, Carbon $end, ?array $types = null): float
    {
        return (float) RentalPayment::query()
            ->whereBetween('paid_at', [$start, $end])
            ->when($types, fn ($query) => $query->whereIn('payment_type', $types))
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type = 'refund' THEN -amount ELSE amount END), 0) as total")
            ->value('total');
    }

    private function pickupTodayQuery(Carbon $startOfDay, Carbon $endOfDay)
    {
        return Rental::query()
            ->with('customer:id,name,whatsapp_number')
            ->where('status', 'booked')
            ->whereBetween('pickup_at', [$startOfDay, $endOfDay]);
    }

    private function returnTodayQuery(Carbon $startOfDay, Carbon $endOfDay)
    {
        return Rental::query()
            ->with('customer:id,name,whatsapp_number')
            ->whereIn('status', ['picked_up', 'overdue'])
            ->whereBetween('return_due_at', [$startOfDay, $endOfDay]);
    }

    private function overdueQuery(Carbon $now)
    {
        return Rental::query()
            ->with('customer:id,name,whatsapp_number')
            ->whereIn('status', ['picked_up', 'overdue'])
            ->where('return_due_at', '<', $now);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rentalList($rentals): array
    {
        return $rentals
            ->map(fn (Rental $rental): array => [
                'id' => $rental->id,
                'invoice_number' => $rental->invoice_number,
                'customer_name' => $rental->customer?->name,
                'status' => $rental->status,
                'payment_status' => $rental->payment_status,
                'pickup_at' => $rental->pickup_at,
                'return_due_at' => $rental->return_due_at,
                'total_amount' => $rental->total_amount,
                'remaining_amount' => $rental->remaining_amount,
                'created_at' => $rental->created_at,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{date: string, total: float}>
     */
    private function dailyRevenue(): array
    {
        $startDate = now()->copy()->subDays(6)->startOfDay();
        $rows = RentalPayment::query()
            ->where('paid_at', '>=', $startDate)
            ->selectRaw("DATE(paid_at) as payment_date, COALESCE(SUM(CASE WHEN payment_type = 'refund' THEN -amount ELSE amount END), 0) as total")
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->pluck('total', 'payment_date');

        return collect(range(0, 6))
            ->map(function (int $dayOffset) use ($startDate, $rows): array {
                $date = $startDate->copy()->addDays($dayOffset)->toDateString();

                return [
                    'date' => $date,
                    'total' => (float) ($rows[$date] ?? 0),
                ];
            })
            ->all();
    }
}
