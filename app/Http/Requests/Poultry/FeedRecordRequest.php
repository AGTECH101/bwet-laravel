<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class FeedRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'poultry_batch_id' => 'required|exists:poultry_batches,id',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'date' => 'required|date',
            'feed_used' => 'required|numeric|min:0.001',
        ];
    }
}