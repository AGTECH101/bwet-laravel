<?php

namespace App\Console\Commands;

use App\Models\Poultry\Batch;
use Illuminate\Console\Command;

class MigrateBatchState extends Command
{
    protected $signature = 'batch:migrate-state';
    protected $description = 'Populate checkpoint columns for existing batches';

    public function handle()
    {
        $batches = Batch::all();
        foreach ($batches as $batch) {
            // Calculate current count from flock records (or use remaining_flock)
            $count = $batch->remaining_flock;
            $avgWeight = $batch->getCurrentAverageWeight();
            $weight = $count * $avgWeight;
            $cost = $batch->calculateTotalInvestment() - $batch->cost_allocated_so_far;

            $batch->current_count = $count;
            $batch->current_weight_kg = $weight;
            $batch->current_cost = $cost;
            $batch->current_average_weight = $avgWeight;
            $batch->current_average_cost = $count > 0 ? $cost / $count : 0;
            $batch->total_weight_gain = $batch->total_weight_gain ?? 0;
            $batch->save();

            $this->info("Migrated batch {$batch->batch_id}");
        }

        $this->info('All batches migrated successfully.');
    }
}