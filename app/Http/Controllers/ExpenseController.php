<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $expenses = Expense::query()
            ->with('creator:id,name')
            ->when($search !== '', fn ($query) => $query->where('description', 'like', "%{$search}%"))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('expense_date', '<=', $dateTo))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Expense $expense): array => [
                'id' => $expense->id,
                'description' => $expense->description,
                'amount' => (int) $expense->amount,
                'category' => $expense->category,
                'expense_date' => $expense->expense_date instanceof Carbon
                    ? $expense->expense_date->toDateString()
                    : $expense->expense_date,
                'notes' => $expense->notes,
                'creator' => $expense->creator ? ['name' => $expense->creator->name] : null,
                'created_at' => $expense->created_at->toISOString(),
            ]);

        $totalAmount = Expense::query()
            ->when($search !== '', fn ($query) => $query->where('description', 'like', "%{$search}%"))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('expense_date', '<=', $dateTo))
            ->sum('amount');

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'totalAmount' => (int) $totalAmount,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Expenses/Create');
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Expense::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function edit(Expense $expense): Response
    {
        return Inertia::render('Expenses/Edit', [
            'expense' => [
                'id' => $expense->id,
                'description' => $expense->description,
                'amount' => (int) $expense->amount,
                'category' => $expense->category,
                'expense_date' => $expense->expense_date->toDateString(),
                'notes' => $expense->notes,
            ],
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
