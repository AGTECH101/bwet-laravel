<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\ExpenseRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\Expense;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    public function index(Batch $batch)
    {
        Gate::authorize('view', $batch);
        $expenses = $batch->expenses()->with('recordedBy')->latest('date')->paginate(20);
        return view('sectors.poultry.expenses.index', compact('batch', 'expenses'));
    }

    public function create(?Batch $batch = null)
    {
        Gate::authorize('create', Expense::class);

        $batches = Batch::query()
            ->orderBy('start_date', 'desc')
            ->get();

        return view('sectors.poultry.expenses.create', compact('batch', 'batches'));
    }

    public function store(ExpenseRequest $request)
    {
        Gate::authorize('create', Expense::class);

        $data = $request->validated();
        $data['recorded_by_id'] = auth()->id();

        $expense = Expense::create($data);
        $expense->batch?->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $expense->batch)
            ->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        Gate::authorize('update', $expense);
        return view('sectors.poultry.expenses.edit', compact('expense'));
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        Gate::authorize('update', $expense);

        $expense->update($request->validated());
        $expense->batch?->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $expense->batch)
            ->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        Gate::authorize('delete', $expense);

        $batch = $expense->batch;
        $expense->delete();
        $batch?->updateCachedMetrics();

        return redirect()->back()->with('success', 'Expense deleted.');
    }
}