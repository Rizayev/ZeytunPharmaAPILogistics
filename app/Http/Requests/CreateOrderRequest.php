<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_data.*.order_name' => 'required|string',
            'order_data.*.longitude' => 'required|string',
            'order_data.*.latitude' => 'required|string',
            'order_data.*.start_interval_date' => 'required',
            'order_data.*.end_interval_date' => 'required',

            'order_data.*.price' => 'numeric',
            'order_data.*.weight' => 'numeric',

            'order_data.*.client_name' => 'string',
            'order_data.*.client_phone' => 'string',
            'order_data.*.client_email' => 'string|email',
            'order_data.*.client_comment' => 'string',
            'unit_id' => 'required|integer'

        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
