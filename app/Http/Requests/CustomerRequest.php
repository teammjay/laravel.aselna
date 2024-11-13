<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
        // request will be,
        // first_name, last_name, user_id:nullable, group:nullable, store_id:nullable(but from store table), emails(array of email), phones(array of phone), addresses(array of address)
        return [
            'first_name' => 'required|string|max:30',
            'last_name' => 'required|string|max:30',
            'full_name' => 'required|string|max:60',
            'store_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'group' => 'nullable|string|max:30',
//            'emails' => 'array|nullable',
//            'emails.*.email' => 'required|string|max:255',
//            'phones' => 'array|nullable',
//            'phones.*.phone' => 'required|string|max:30',
//            'addresses' => 'array|nullable',
//            'addresses.*.address' => 'required|string|max:255',
        ];
    }
}
