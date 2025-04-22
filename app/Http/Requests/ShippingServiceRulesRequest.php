<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class ShippingServiceRulesRequest extends FormRequest
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
            "priority" => "required",
            /*"min_price" => "required",
            "max_price" => "required",
            "weight_operator" => "required",
            "price_operator" => "required",
            "quantity_operator" => "required",
            "min_quantity" => "required",
            "max_quantity" => "required"*/
        ];
    }
}
