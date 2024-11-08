<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:30',
            'notes1' => 'nullable|string|max:255',
            'notes2' => 'nullable|string|max:255',
            'notes3' => 'nullable|string|max:255',
            'order_no' => 'nullable|integer',
            'image' => 'nullable|string|max:255',
            'thumbnail_image' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50',
            'status' => 'nullable|in:on,off'
        ];
    }
}
