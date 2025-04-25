<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintersRequest extends FormRequest
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
            'name' => 'required',
            'label_format' => 'required',
            'dpi' => 'required',
            'status' => 'required',
            'printer_type' => 'required'
        ];
    }
}
