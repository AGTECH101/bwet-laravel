<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class WeightRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'poultry_batch_id' => 'required|exists:poultry_batches,id',
            'date' => 'required|date',
            'individual_weights' => 'required|array|min:1|max:10',
            'individual_weights.*' => 'numeric|min:0.001|max:5',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}