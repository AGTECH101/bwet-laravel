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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $itemId = $this->input('inventory_item_id');
            $quantityUsed = (float) $this->input('quantity_used', 0);

            if (! $itemId) {
                return;
            }

            $item = \App\Models\Poultry\InventoryItem::find($itemId);
            if (! $item) {
                return;
            }

            if ($quantityUsed > (float) $item->quantity_in_stock) {
                $validator->errors()->add('quantity_used', 'Usage cannot exceed the remaining inventory quantity left (available: ' . number_format((float) $item->quantity_in_stock, 3) . ' ' . $item->unit . ').');
            }
        });
    }
}