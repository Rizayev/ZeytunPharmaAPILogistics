<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSimpleOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'route_ids' => 'required|array',
            'unit_id' => 'required|integer',
            'date' => 'date_format:d-m-Y'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
