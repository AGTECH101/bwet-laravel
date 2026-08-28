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

        // If a batch is linked, ensure it's active
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

            // If a batch is linked, update its state (add cost)
            if ($batch) {
                $changes = [
                    'count' => 0,
                    'weight' => 0,
                    'cost' => $expense->amount,
                ];
                $batch->updateState($changes, 'expense');
                $batch->updateCachedMetrics(); // optional for backward compatibility
            }

            return $expense;
        });

        // Redirect based on whether there's a batch
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
            // Update the expense record
            $expense->update($data);

            // If the batch is linked, adjust the state by the difference
            if ($expense->batch) {
                $batch = $expense->batch;
                $diff = $newAmount - $oldAmount;
                if ($diff != 0) {
                    $changes = [
                        'count' => 0,
                        'weight' => 0,
                        'cost' => $diff,
                    ];
                    $batch->updateState($changes, 'expense');
                    $batch->updateCachedMetrics();
                }
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

            // Reverse the cost effect
            if ($batch) {
                $changes = [
                    'count' => 0,
                    'weight' => 0,
                    'cost' => -$expense->amount,
                ];
                $batch->updateState($changes, 'expense');
                $batch->updateCachedMetrics();
            }

            $expense->delete();
        });

        return redirect()->back()->with('success', 'Expense deleted.');
    }
}