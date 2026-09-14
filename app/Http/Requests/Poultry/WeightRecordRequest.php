<?php

namespace App\Http\Requests\Poultry;

use Illuminate\Foundation\Http\FormRequest;

class WeightRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Convert newline-separated weights into an array before validation.
     *
     * The forms post weight_1..weight_10 which are aggregated client-side
     * into individual_weights[]. This handles the case where the field is
     * submitted as a newline-separated string (e.g. from a CLI test or a
     * legacy form).
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('individual_weights'))) {
            $rawWeights = preg_split('/\r\n|\r|\n/', trim((string) $this->input('individual_weights')));
            $weights = array_values(array_filter(
                array_map(static fn ($value) => trim((string) $value), $rawWeights),
                static fn ($value) => $value !== ''
            ));

            $this->merge([
                'individual_weights' => $weights,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'poultry_batch_id'    => 'required|exists:poultry_batches,id',
            'date'                => 'required|date',
            'individual_weights'  => ['required', 'array', 'min:1', 'max:10'],
            'individual_weights.*'=> 'numeric|min:0.001|max:5',
            'notes'               => 'nullable|string|max:1000',
        ];
    }

    // CV policy: any variation is accepted.
    //   CV < 10  → 'excellent'  (badge: green)
    //   CV < 12  → 'caution'    (badge: blue)
    //   CV < 15  → 'warning'    (badge: yellow)
    //   CV >= 15 → 'high'       (badge: orange, warning flash)
    // The record is always saved with is_valid_sample = true and used
    // everywhere downstream (average weight, FCR, batch metrics).
}