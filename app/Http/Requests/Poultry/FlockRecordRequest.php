<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class FlockRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize blank counters to 0 before validation runs.
     *
     * Laravel's ConvertEmptyStringsToNull middleware turns empty form fields
     * into null. The flock_records counters are NOT NULL in the database,
     * so we normalize them here so the entire request pipeline sees 0
     * rather than null.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'mortality' => $this->normalizeCounter($this->input('mortality')),
            'culls'     => $this->normalizeCounter($this->input('culls')),
            'slaughter' => $this->normalizeCounter($this->input('slaughter')),
        ]);
    }

    protected function normalizeCounter($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    public function rules(): array
    {
        return [
            'poultry_batch_id'     => 'required|exists:poultry_batches,id',
            'date'                 => 'required|date',
            'mortality'            => 'nullable|integer|min:0',
            'culls'                => 'nullable|integer|min:0',
            'slaughter'            => 'nullable|integer|min:0',
            'slaughter_avg_weight' => 'nullable|numeric|min:0.001|required_if:slaughter,>0',
            'notes'                => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'slaughter_avg_weight.required_if' => 'Please enter the average weight of the slaughtered birds.',
            'slaughter_avg_weight.min'         => 'The average weight must be greater than 0.',
        ];
    }
}