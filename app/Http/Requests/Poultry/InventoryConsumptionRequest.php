<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class InventoryConsumptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'poultry_batch_id' => 'nullable|exists:poultry_batches,id',
            'quantity_used' => 'required|numeric|min:0.001',
            'date' => 'required|date',
        ];
    }
}