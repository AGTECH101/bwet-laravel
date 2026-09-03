<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\ExpenseRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\Expense;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

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
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('sectors.poultry.expenses.create', compact('batch', 'batches'));
    }

    public function store(ExpenseRequest $request)
    {
        Gate::authorize('create', Expense::class);

        $data = $request->validated();

        if (!empty($data['poultry_batch_id'])) {
            $batch = Batch::findOrFail($data['poultry_batch_id']);
            if ($batch->status !== 'active') {
                return redirect()->back()->with('error', 'Cannot add expenses to a closed or completed batch.');
            }
        } else {
            $batch = null;
        }

        $data['recorded_by_id'] = auth()->id();

        DB::transaction(function () use ($data, $batch) {
            $expense = Expense::create($data);

            if ($batch) {
                $batch->current_cost += $expense->amount;
                $batch->current_average_cost = $batch->current_count > 0
                    ? $batch->current_cost / $batch->current_count
                    : 0;
                $batch->total_expenses += $expense->amount;
                $batch->save();
                $batch->updateCachedMetrics();
            }
        });

        if ($batch) {
            return redirect()->route('poultry.batches.show', $batch)
                ->with('success', 'Expense recorded.');
        } else {
            return redirect()->route('poultry.forms.hub')
                ->with('success', 'General expense recorded.');
        }
    }

    public function edit(Expense $expense)
    {
        Gate::authorize('update', $expense);
        return view('sectors.poultry.expenses.edit', compact('expense'));
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        Gate::authorize('update', $expense);

        $data = $request->validated();
        $oldAmount = $expense->amount;
        $newAmount = $data['amount'];

        DB::transaction(function () use ($expense, $data, $oldAmount, $newAmount) {
            $batch = $expense->batch;

            if ($batch) {
                // Reverse old amount
                $batch->current_cost -= $oldAmount;
                $batch->total_expenses -= $oldAmount;
                $batch->current_average_cost = $batch->current_count > 0
                    ? $batch->current_cost / $batch->current_count
                    : 0;
            }

            $expense->update($data);

            if ($batch) {
                // Apply new amount
                $batch->current_cost += $newAmount;
                $batch->total_expenses += $newAmount;
                $batch->current_average_cost = $batch->current_count > 0
                    ? $batch->current_cost / $batch->current_count
                    : 0;
                $batch->save();
                $batch->updateCachedMetrics();
            }
        });

        return redirect()->route('poultry.batches.show', $expense->batch)
            ->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        Gate::authorize('delete', $expense);

        DB::transaction(function () use ($expense) {
            $batch = $expense->batch;

            if ($batch) {
                $batch->current_cost -= $expense->amount;
                $batch->total_expenses -= $expense->amount;
                $batch->current_average_cost = $batch->current_count > 0
                    ? $batch->current_cost / $batch->current_count
                    : 0;
                $batch->save();
                $batch->updateCachedMetrics();
            }

            $expense->delete();
        });

        return redirect()->back()->with('success', 'Expense deleted.');
    }
}