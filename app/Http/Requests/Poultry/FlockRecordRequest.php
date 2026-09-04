<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class FlockRecordRequest extends FormRequest
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
            'mortality' => 'nullable|integer|min:0',
            'culls' => 'nullable|integer|min:0',
            'slaughter' => 'nullable|integer|min:0',
            'slaughter_avg_weight' => 'nullable|numeric|min:0.001|required_if:slaughter,>0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'slaughter_avg_weight.required_if' => 'Please enter the average weight of the slaughtered birds.',
            'slaughter_avg_weight.min' => 'The average weight must be greater than 0.',
        ];
    }
}