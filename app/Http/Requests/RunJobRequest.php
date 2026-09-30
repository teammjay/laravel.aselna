<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * "Run Now" on the Saved Jobs / Cron Jobs dashboards.
 *
 * scope = default  => run as the scheduler would (no store restriction passed to the job)
 * scope = selected => run only for store_ids (at least one)
 * Omitted scope behaves as "default", so the existing plain Run Now forms keep working.
 */
class RunJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scope' => 'nullable|in:default,selected',
            'store_ids' => 'required_if:scope,selected|array|min:1',
            'store_ids.*' => 'string|distinct|exists:store,id',
        ];
    }

    public function messages(): array
    {
        return [
            'store_ids.required_if' => 'Select at least one store, or choose the default stores.',
        ];
    }

    /**
     * The selected store ids, or null for the default behaviour.
     */
    public function storeIds(): ?array
    {
        return $this->input('scope') === 'selected' ? array_values($this->input('store_ids', [])) : null;
    }
}
