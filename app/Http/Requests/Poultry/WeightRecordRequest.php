<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class WeightRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('individual_weights'))) {
            $rawWeights = preg_split('/\r\n|\r|\n/', trim((string) $this->input('individual_weights')));
            $weights = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $rawWeights), static fn ($value) => $value !== ''));

            $this->merge([
                'individual_weights' => $weights,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'poultry_batch_id' => 'required|exists:poultry_batches,id',
            'date' => 'required|date',
            'individual_weights' => ['required', 'array', 'min:1', 'max:10'],
            'individual_weights.*' => 'numeric|min:0.001|max:5',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $weights = $this->input('individual_weights', []);

            if (! is_array($weights) || count($weights) < 2) {
                return;
            }

            $numericWeights = array_values(array_filter(array_map(static fn ($item) => is_numeric($item) ? (float) $item : null, $weights), static fn ($item) => $item !== null));

            if (count($numericWeights) < 2) {
                return;
            }

            $mean = array_sum($numericWeights) / count($numericWeights);
            if ($mean <= 0) {
                return;
            }

            $variance = array_sum(array_map(static fn ($weight) => ($weight - $mean) ** 2, $numericWeights)) / count($numericWeights);
            $stdDev = sqrt($variance);
            $cv = ($stdDev / $mean) * 100;

            if ($cv >= 15) {
                $validator->errors()->add('individual_weights', 'High weight variation detected (CV >= 15%). Please re-take the sample before saving.');
            }
        });
    }
}