<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\FlockRecordRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FlockRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FlockRecordController extends Controller
{
    public function index(Batch $batch)
    {
        Gate::authorize('view', $batch);
        $records = $batch->flockRecords()->with('recordedBy')->latest('date')->paginate(20);
        return view('sectors.poultry.flock-records.index', compact('batch', 'records'));
    }

    public function create(Batch $batch)
    {
        Gate::authorize('create', FlockRecord::class);
        return view('sectors.poultry.flock-records.create', compact('batch'));
    }

    public function store(FlockRecordRequest $request)
    {
        Gate::authorize('create', FlockRecord::class);

        $data = $request->validated();
        $data['recorded_by_id'] = auth()->id();

        $record = FlockRecord::create($data);

        // Update batch metrics
        $record->batch->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $record->batch)
            ->with('success', 'Flock record saved.');
    }

    public function edit(FlockRecord $flockRecord)
    {
        Gate::authorize('update', $flockRecord);
        return view('sectors.poultry.flock-records.edit', compact('flockRecord'));
    }

    public function update(FlockRecordRequest $request, FlockRecord $flockRecord)
    {
        Gate::authorize('update', $flockRecord);

        $flockRecord->update($request->validated());
        $flockRecord->batch->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $flockRecord->batch)
            ->with('success', 'Flock record updated.');
    }

    public function destroy(FlockRecord $flockRecord)
    {
        Gate::authorize('delete', $flockRecord);

        $batch = $flockRecord->batch;
        $flockRecord->delete();
        $batch->updateCachedMetrics();

        return redirect()->back()->with('success', 'Flock record deleted.');
    }
}