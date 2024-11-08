<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceRequest extends FormRequest
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
            'dimensions_height' => 'nullable|string|max:25',
            'dimensions_width' => 'nullable|string|max:25',
            'name' => 'required',
            'release_date' => 'nullable|string|max:25',
            'image' => 'nullable|string|max:255',
            'thumbnail_image' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'order' => 'required',
            'code' => 'required',
            'dimensions_length' => 'nullable|string|max:25',
            'dimensions_length_unit' => 'nullable|string|max:25',
            'device_sub_type_id' => 'nullable',
            'device_series_id' => 'nullable',
            'rating_id' => 'nullable',
            'screen_id' => 'nullable',
            'brand_id' => 'nullable',
            'device_type_id' => 'nullable',
            'status' => 'required|in:on,off',
        ];
    }
}
