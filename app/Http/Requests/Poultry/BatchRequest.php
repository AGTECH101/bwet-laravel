<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class BatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_id' => 'nullable|string|max:20|unique:poultry_batches,batch_id,' . $this->route('batch'),
            'name' => 'required|string|max:100',
            'hatchery' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'starting_flock' => 'required|integer|min:0', // ← changed from 1 to 0
            'phase' => 'required|in:brooding,batch',
            'pen_id' => 'nullable|exists:pens,id',
            'initial_chicken_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,closed,completed',
        ];
    }
}