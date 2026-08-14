<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'poultry_batch_id' => 'nullable|exists:poultry_batches,id',
            'date' => 'required|date',
            'category' => 'required|in:medication,vaccination,labor,utilities,maintenance,transport,packaging,other',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'receipt_number' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:100',
        ];
    }
}