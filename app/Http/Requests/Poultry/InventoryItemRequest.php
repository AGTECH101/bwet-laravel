<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class InventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'category' => 'required|in:feed,vaccine,medicine,consumables,packaging,other',
            'unit' => 'required|in:kg,g,l,ml,unit,bag,spoon,box',
            'quantity_in_stock' => 'required|numeric|min:0',
            'minimum_quantity' => 'nullable|numeric|min:0',
            'vendor' => 'nullable|string|max:250',
            'cost_per_unit' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}