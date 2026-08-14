<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class FlockRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handled by policies
    }

    public function rules(): array
    {
        return [
            'poultry_batch_id' => 'required|exists:poultry_batches,id',
            'date' => 'required|date',
            'mortality' => 'nullable|integer|min:0',
            'culls' => 'nullable|integer',
            'slaughter' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}